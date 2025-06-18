<?php 

namespace Paw\App\Models;

use Paw\Core\Model;

use Exception;
use PDOException;
use Paw\Core\Traits\Loggable;

class ProductosCollection extends Model
{
    use Loggable;
    private $table = 'producto';
    public $usuariosAdmin = [];

    public function __construct($qb = null, $logger = null, $usuariosAdmin = []) {
        if ($qb !== null) {
            parent::setQueryBuilder($qb);
        }

        if ($logger !== null) {
            parent::setLogger($logger);
        }
        if ($usuariosAdmin !== []){
            $this->setUsuariosAdmin($usuariosAdmin);
        }
    }

    public function setUsuariosAdmin(array $roles): void
    {
        $this->usuariosAdmin = $roles;
    }

    public function getUsuariosAdmin(): array
    {
        return $this->usuariosAdmin;
    }

    // Método extra útil para verificar si un rol está autorizado
    public function esAdminPorRol(string $rol): bool
    {
        return in_array($rol, $this->usuariosAdmin, true);
    }
    public function actualizarProducto(array $data)
    {
        try {
            $this->logger->info("Actualizando producto ID: ", [$data]);
        
            $resultado = $this->queryBuilder->update('producto', $data, ['id' => $data['id']]);
    
            if (!$resultado) {
                throw new Exception("No se pudo actualizar el producto.");
            }
    
            return true;
    
        } catch (Exception $e) {
            $this->logger->error("Error al actualizar producto: " . $e->getMessage());
            throw $e;
        }
    }
    

    public function obtenerStockActual($productoId)
    {
        try {
            $result = $this->queryBuilder->select('producto', 'stock_inicial', ['id' => $productoId]);

            if (!$result || !isset($result[0]['stock_inicial'])) {
                throw new Exception("No se encontró el producto con ID $productoId");
            }

            $stockInicial = (int) $result[0]['stock_inicial'];

            $sql = "
                SELECT 
                    SUM(CASE WHEN tipo_movimiento = 'in' THEN cantidad ELSE 0 END) AS total_in,
                    SUM(CASE WHEN tipo_movimiento = 'out' THEN cantidad ELSE 0 END) AS total_out
                FROM movimiento_inventario
                WHERE producto_id = :producto_id
            ";

            $params = ['producto_id' => $productoId];
            $movimientos = $this->queryBuilder->query($sql, $params);

            $in = (int) ($movimientos[0]['total_in'] ?? 0);
            $out = (int) ($movimientos[0]['total_out'] ?? 0);

            return $stockInicial + $in - $out;

        } catch (Exception $e) {
            $this->logger->error("Error en obtenerStockActual: " . $e->getMessage(), ['producto_id' => $productoId]);
            throw $e;
        }
    }

    public function registrarMovimientoInventario(array $data)
    {
        try {
            $this->logger->info("Registrando movimiento inventario:", $data);

            list($idInsertado, $success) = $this->queryBuilder->insert('movimiento_inventario', [
                'factura_id' => $data['factura_id'] ?? null,
                'producto_id' => $data['producto_id'],
                'fecha_movimiento' => date('Y-m-d H:i:s'),
                'tipo_movimiento' => $data['tipo_movimiento'],
                'cantidad' => $data['cantidad'],
                'descripcion_movimiento' => $data['descripcion_movimiento'] ?? null,
                'path_comprobante_decomiso' => $data['path_comprobante_decomiso'] ?? null,
            ]);

            if (!$success) {
                throw new Exception("No se pudo registrar el movimiento de inventario.");
            }

            return $idInsertado;

        } catch (Exception $e) {
            $this->logger->error("Error al registrar movimiento de inventario: " . $e->getMessage());
            throw $e;
        }
    }


    public function getMovimientosInventario($idProducto)
    {
        try {
            $sql = "
                SELECT 
                    id,
                    factura_id,
                    fecha_movimiento,
                    tipo_movimiento,
                    cantidad,
                    descripcion_movimiento,
                    path_comprobante_decomiso
                FROM movimiento_inventario
                WHERE producto_id = :id
                ORDER BY fecha_movimiento DESC
            ";
    
            $params = [':id' => $idProducto];
            return $this->queryBuilder->query($sql, $params);
        } catch (\Exception $e) {
            $this->logger->error("Error al obtener movimientos de inventario: " . $e->getMessage());
            return [];
        }
    }

    
    public function obtenerComprobanteDecomiso($productoId, $fechaMovimiento)
    {
        try {
            $desde = date('Y-m-d H:i:s', strtotime($fechaMovimiento));
            $hasta = date('Y-m-d H:i:s', strtotime($fechaMovimiento) + 1);
    
            $this->logger->debug("🔍 Buscando comprobante para decomiso", [
                'producto_id' => $productoId,
                'fecha_movimiento_original' => $fechaMovimiento,
                'desde' => $desde,
                'hasta' => $hasta
            ]);
    
            $sql = "
                SELECT path_comprobante_decomiso
                FROM movimiento_inventario
                WHERE producto_id = :producto_id
                AND tipo_movimiento = 'out'
                AND fecha_movimiento BETWEEN :desde AND :hasta
                LIMIT 1
            ";
    
            $params = [
                ':producto_id' => $productoId,
                ':desde' => $desde,
                ':hasta' => $hasta
            ];
    
            $resultado = $this->queryBuilder->query($sql, $params);
    
            $this->logger->debug("📄 Resultado consulta comprobante:", $resultado);
    
            return $resultado[0]['path_comprobante_decomiso'] ?? null;
    
        } catch (Exception $e) {
            $this->logger->error("❌ Error al obtener comprobante de decomiso: " . $e->getMessage());
            return null;
        }
    }
    
public function getStockActual($idProducto)
{
    try {
        $producto = $this->getById($idProducto);
        $stockInicial = (float) $producto['stock_inicial'] ?? 0;

        // Entradas (in)
        $sqlIn = "SELECT SUM(cantidad) AS total_in 
                  FROM movimiento_inventario 
                  WHERE producto_id = :id 
                  AND tipo_movimiento = 'in'";
        $totalIn = (float) ($this->queryBuilder->query($sqlIn, [':id' => $idProducto])[0]['total_in'] ?? 0);

        // Salidas (out)
        $sqlOut = "SELECT SUM(cantidad) AS total_out 
                   FROM movimiento_inventario 
                   WHERE producto_id = :id 
                   AND tipo_movimiento = 'out'";
        $totalOut = (float) ($this->queryBuilder->query($sqlOut, [':id' => $idProducto])[0]['total_out'] ?? 0);

        // Traslados pendientes desde cualquier dependencia
        $sqlTransito = "SELECT SUM(cantidad) AS transito 
                        FROM traslado_stock 
                        WHERE producto_id = :id 
                        AND estado = 'pendiente' 
                        AND fecha_vencimiento > NOW()";
        $stockEnTransito = (float) ($this->queryBuilder->query($sqlTransito, [':id' => $idProducto])[0]['transito'] ?? 0);

        // Log
        $this->logger->debug("🧮 Stock actual con tránsito descontado:", [
            'stock_inicial' => $stockInicial,
            'in' => $totalIn,
            'out' => $totalOut,
            'transito' => $stockEnTransito
        ]);

        // Stock disponible = real - en tránsito pendiente
        return $stockInicial + $totalIn - $totalOut - $stockEnTransito;

    } catch (Exception $e) {
        $this->logger->error("❌ Error al calcular stock actual: " . $e->getMessage());
        return 0;
    }
}

    

    public function contarSinPrecio(): int
    {
        $sql = "
            SELECT COUNT(*) as total 
            FROM producto p
            WHERE NOT EXISTS (
                SELECT 1 
                FROM precio pr 
                WHERE pr.id_producto = p.id
            )
        ";
        $resultado = $this->queryBuilder->query($sql);
        return (int) ($resultado[0]['total'] ?? 0);
    }
    

    public function contarTodos(): int
    {
        $sql = "SELECT COUNT(*) as total FROM producto";
        $resultado = $this->queryBuilder->query($sql);
        return (int) ($resultado[0]['total'] ?? 0);
    }

    public function contarPorUnidadMedida(): array
    {
        $sql = "SELECT unidad_medida, COUNT(*) as cantidad FROM producto GROUP BY unidad_medida";
        $resultado = $this->queryBuilder->query($sql);
    
        $this->logger->info("contarPorUnidadMedida: ", [$resultado]);

        $formateado = [];
        foreach ($resultado as $row) {
            $formateado[$row['unidad_medida']] = (int) $row['cantidad'];
        }
    
        return $formateado;
    }
    


    public function getDependencias($dependendiaId = null)
    {
        try {

            $this->logger->info("input getDependencia: ", [$dependendiaId]);

            if (!is_null($dependendiaId)) {
                // Filtra por ID si se especifica
                $dependencias = $this->queryBuilder->select('dependencia', '*', ['id' => $dependendiaId]);
            } else {
                // Devuelve todas si no se especifica
                $dependencias = $this->queryBuilder->select('dependencia', '*');
            }
    
            $this->logger->info("getDependencias: ", [$dependencias]);

            return $dependencias;
        } catch (Exception $e) {
            error_log('Error en getDependencias: ' . $e->getMessage());
            return [];
        }
    }
    
    public function getById($id)
    {
        try {
            // Validar que el ID sea un número
            if (!is_numeric($id)) {
                throw new Exception("El ID proporcionado no es válido.");
            }
    
            // Consultar el producto en la base de datos
            $resultado = $this->queryBuilder->select('producto', '*', ['id' => $id]);
    
            // Verificar si se encontró un resultado
            if (!$resultado) {
                throw new Exception("No se encontró el producto con ID: $id");
            }
    
            // Retornar el primer registro encontrado (en teoría, solo debería haber uno)
            return $resultado[0];
    
        } catch (Exception $e) {
            $this->logger->error("Error en getById", ['id' => $id, 'error' => $e->getMessage()]);
            throw new Exception("Error al obtener el producto: " . $e->getMessage());
        }
    }
    

    public function getHistorialPrecios($idProducto)
    {
        try {
            $sql = "
                SELECT 
                    precio,
                    fecha_precio,
                    pv_autorizacion_consejo
                FROM precio
                WHERE id_producto = :id
                ORDER BY fecha_precio DESC
            ";

            $params = [':id' => $idProducto];
            $result = $this->queryBuilder->query($sql, $params);

            return $result ?: []; // Si no hay resultados, devolvé un array vacío
        } catch (PDOException $e) {
            $this->logger->error("Error al obtener historial de precios: " . $e->getMessage());
            return [];
        }
    }


    public function getProductosALaVenta()
    {
        try {
        $result = $this->queryBuilder->select('producto', '*');

            if (!empty($result)) {
                $this->logger->info("Datos de productos recuperados con éxito: ", $result);
                $result[0]['exito'] = true;
                return $result; // Suponiendo que select devuelve un array de resultados
            } else {
                $this->logger->error("No se encontró listado de productos");
                return ["exito" => false ];
            }
        } catch (PDOException $e) {
            $this->logger->error("Error al recuperar los datos de los productos: " . $e->getMessage());
            return ["exito" => false ];
        } catch (Exception $e) {
            $this->logger->error("Ocurrió un error al obtener los datos de los productos: " . $e->getMessage());
            return ["exito" => false ];
        }        
    }

    public function getDetalleProducto($id) 
    {
        try {
            $result = $this->queryBuilder->select('producto', '*', ['id' => $id]);

            $datosDependencia = $this->getDependencias($result[0]['id_unidad_q_fabrica']);

            if (!empty($result)) {
                $this->logger->info("Datos de producto recuperados con éxito: ", [$result, $result[0]['id'], $datosDependencia]);
                $result[0]['exito'] = true;
                $result[0]['descripcion_dependencia'] = $datosDependencia[0]['descripcion'];
                return $result[0]; // Suponiendo que select devuelve un array de resultados
            } else {
                $this->logger->error("No se encontró detalle del producto");
                return ["exito" => false ];
            }            
        }catch (PDOException $e) {
            $this->logger->error("Error al recuperar los datos del producto: " . $e->getMessage());
        }
    }
    public function getDetalleProductoYUltimoPrecio($id) 
    {
        try {
            $result = $this->queryBuilder->obtenerProductosConPrecioMasReciente(null, $id);

            if (!empty($result)) {
                $this->logger->info("Datos de producto recuperados con éxito: ", $result);
                // $result[0]['exito'] = true;
                return $result; // Suponiendo que select devuelve un array de resultados
            } else {
                $this->logger->error("No se encontró detalle del producto");
                return ["exito" => false ];
            }            
        }catch (PDOException $e) {
            $this->logger->error("Error al recuperar los datos del producto: " . $e->getMessage());
        }
    }

    public function getProductosConUltimoPrecio($usuarioDependencia = null, $rolDelUsuario = null)
    {
        try {
            $sql = "
                SELECT 
                    p.*,
                    pr.precio,
                    pr.fecha_precio,
                    pr.pv_autorizacion_consejo,
                    d.descripcion AS unidad_productora
                FROM producto p
                LEFT JOIN (
                    SELECT pr1.*
                    FROM precio pr1
                    INNER JOIN (
                        SELECT id_producto, MAX(fecha_precio) AS ultima_fecha
                        FROM precio
                        GROUP BY id_producto
                    ) pr2 ON pr1.id_producto = pr2.id_producto AND pr1.fecha_precio = pr2.ultima_fecha
                ) pr ON p.id = pr.id_producto
                LEFT JOIN dependencia d ON p.id_unidad_q_fabrica = d.id
                WHERE p.estado = 'a_la_venta'
            ";

            $params = [];

            // Solo filtra por dependencia si el usuario NO es administrador
            if ($rolDelUsuario === PUNTO_VENTA && !is_null($usuarioDependencia)) {
                $sql .= " AND p.id_unidad_q_fabrica = :dependencia";
                $params['dependencia'] = $usuarioDependencia;
            }


            $sql .= " ORDER BY p.created_at DESC";

            $productos = $this->queryBuilder->query($sql, $params);
            $productosConStock = [];

            foreach ($productos as $producto) {
                $stock = $this->getStockActual($producto['id']);
                $producto['stock_actual'] = $stock;
                $productosConStock[] = $producto;
            }

            return $productosConStock;
        } catch (Exception $e) {
            $this->logger->error("❌ Error al obtener productos con precio más reciente: " . $e->getMessage());
            return [];
        }
    }
    
    
    
    public function insertarPrecio(array $data)
    {
        try {
            $this->logger->info("Insertando nuevo precio con data:", [$data]);
    
            // Agregar hora actual si solo vino la fecha
            if (strlen($data['fecha_precio']) === 10) { // formato 'Y-m-d'
                $data['fecha_precio'] .= ' ' . date('H:i:s');
            }
    
            list($idInsertado, $success) = $this->queryBuilder->insert('precio', [
                'precio' => $data['precio'],
                'pv_autorizacion_consejo' => $data['pv_autorizacion_consejo'] ?? null,
                'fecha_precio' => $data['fecha_precio'],
                'id_producto' => $data['id_producto']
            ]);
    
            if (!$success) {
                throw new Exception("No se pudo insertar el nuevo precio.");
            }
    
            return $idInsertado;
    
        } catch (Exception $e) {
            $this->logger->error("Error al insertar precio: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function crearProducto(array $datos)
    {
        try {
            $sql = "
                INSERT INTO producto (
                    descripcion_proyecto,
                    estado,
                    stock_inicial,
                    unidad_medida,
                    nro_proyecto_productivo,
                    imagen,
                    created_at
                ) VALUES (
                    :descripcion_proyecto,
                    :estado,
                    :stock_inicial,
                    :unidad_medida,
                    :nro_proyecto_productivo,
                    :imagen,
                    NOW()
                )
            ";

            $params = [
                'descripcion_proyecto' => $datos['descripcion_proyecto'],
                'estado' => $datos['estado'],
                'stock_inicial' => $datos['stock_inicial'],
                'unidad_medida' => $datos['unidad_medida'],
                'nro_proyecto_productivo' => $datos['nro_proyecto_productivo'],
                'imagen' => $datos['imagen']
            ];

            $this->queryBuilder->query($sql, $params);
        } catch (Exception $e) {
            $this->logger->error("Error al insertar producto: " . $e->getMessage(), $datos);
            throw $e;
        }
    }


    public function getProductosYPrecios($searchItem=null, $usuarioDependencia=null)
    {
        try {

            $this->logger->info("getProductosYPrecios() llamado con:", [
                'searchItem' => $searchItem,
                'usuarioDependencia' => $usuarioDependencia
            ]);

            $productos = $this->queryBuilder->obtenerProductosConPrecioMasReciente($searchItem, null, $usuarioDependencia);

            return $productos;

        } catch (PDOException $e) {
            $this->logger->error('Error en getProductosYPrecios: ' . $e->getMessage());
            throw new Exception('Error al obtener los productos con precios más recientes.');
        } catch (Exception $e) {
            $this->logger->error('Error inesperado en getProductosYPrecios: ' . $e->getMessage());
            throw new Exception('Ocurrió un error inesperado.');
        }
    }

    public function eliminarProducto($id)
    {
        try {
            if (!is_numeric($id)) {
                throw new Exception("El ID proporcionado no es válido.");
            }
    
            $this->logger->info("Eliminando producto con ID: ", [$id]);
    
            return $this->queryBuilder->delete($this->table, ['id' => $id]);
    
        } catch (Exception $e) {
            $this->logger->error("Error al eliminar producto: " . $e->getMessage());
            throw $e;
        }
    }
    

}