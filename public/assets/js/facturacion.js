document.addEventListener('DOMContentLoaded', () => {
    // Inicializar modales
    const agentModal = new bootstrap.Modal(document.getElementById('agentModal'));
    const productModal = new bootstrap.Modal(document.getElementById('productModal'));
    const condicionVenta = document.getElementById('condicion_venta');
    const totalFacturado = () => parseFloat(document.getElementById('total_facturado').textContent || 0);
    const selectCuotas = document.getElementById('selectCuotas');
    const montoMinimoCuota = parseFloat(document.getElementById('monto_minimo_cuota').value);

    /**
     * ESCUCHADORES DE EVENTOS
     */    
        
    condicionVenta.addEventListener('change', () => {
        const condicion = condicionVenta.value;
        const total = totalFacturado();
    
        if (total > 0) {
            if (condicion === 'codigo_608' || condicion === 'codigo_689') {
                if (total >= montoMinimoCuota) {
                    const maxCuotas = Math.floor(total / montoMinimoCuota);
                    selectCuotas.innerHTML = ''; // limpiar opciones
        
                    for (let i = 1; i <= maxCuotas; i++) {
                        const opt = document.createElement('option');
                        opt.value = i;
                        opt.textContent = `${i} cuota${i > 1 ? 's' : ''}`;
                        selectCuotas.appendChild(opt);
                    }
        
                    selectCuotas.parentElement.classList.remove('d-none');
                } else {
                    mostrarMensajeModal(`El monto total debe ser al menos $${montoMinimoCuota.toLocaleString()} para fraccionar en cuotas.`);
                    selectCuotas.innerHTML = '';
                    selectCuotas.parentElement.classList.add('d-none');
                }
            } else {
                selectCuotas.innerHTML = '';
                selectCuotas.parentElement.classList.add('d-none');
            }
        } else {
            mostrarMensajeModal("Debe haber un monto total mayor a cero para configurar cuotas.");
            selectCuotas.innerHTML = '';
            selectCuotas.parentElement.classList.add('d-none');
        }
        
    });

    // Abrir modal de agentes
    document.getElementById('openAgentModal').addEventListener('click', () => {
        console.log("Abriendo modal de agentes...");
        agentModal.show();
    });

    // Buscar agentes dinámicamente
    document.getElementById('searchAgent').addEventListener('input', (e) => {
        const searchValue = e.target.value;
        console.log("Buscando agentes:", searchValue);

        fetch(`api_get_agentes?search=${searchValue}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest' // Esto ayuda a que el backend detecte que es una solicitud AJAX
            }
        })
        .then(res => res.json())
        .then(data => {
            const tbody = document.querySelector('#agentTable tbody');
            tbody.innerHTML = ''; // 🧹 Limpiar antes de cargar nuevos resultados

            if (data.success && Array.isArray(data.agentes)) {
                data.agentes.forEach(agent => {
                    const tr = document.createElement('tr');

                    // Si está retirado, marcar fila con fondo gris
                    if (agent.estado_agente === 'retirado') {
                        tr.classList.add('table-secondary');
                    }

                    // Nombre
                    const tdNombre = document.createElement('td');
                    tdNombre.textContent = `${agent.nombre} ${agent.apellido}`;

                    // CUIL
                    const tdCuil = document.createElement('td');
                    tdCuil.textContent = agent.cuil ?? '—';

                    // Estado
                    const tdEstado = document.createElement('td');
                    if (agent.estado_agente === 'activo') {
                        tdEstado.innerHTML = `<span class="badge bg-success">Activo</span>`;
                    } else if (agent.estado_agente === 'retirado') {
                        tdEstado.innerHTML = `<span class="badge bg-secondary">${agent.caracter ?? 'Retirado'}</span>`;
                    } else {
                        tdEstado.textContent = '—';
                    }

                    // Destino
                    const tdDestino = document.createElement('td');
                    tdDestino.textContent = agent.descripcion?.trim() ? agent.descripcion : agent.nombre_dependencia;

                    // Botón seleccionar
                    const tdAccion = document.createElement('td');
                    const btnSelect = document.createElement('button');
                    btnSelect.textContent = "Seleccionar";
                    btnSelect.classList.add('btn', 'btn-sm', 'btn-primary');
                    btnSelect.addEventListener('click', () => {
                        document.getElementById('selectedAgent').textContent = `${agent.nombre} ${agent.apellido}`;
                        document.getElementById('agente').value = agent.id;
                        document.getElementById('destino_agente').textContent = `(Destino: ${tdDestino.textContent})`;
                        console.log("Agente seleccionado:", agent);
                        agentModal.hide();
                    });
                    tdAccion.appendChild(btnSelect);

                    // Agregar todas las celdas
                    tr.appendChild(tdNombre);
                    // tr.appendChild(tdCuil);
                    tr.appendChild(tdEstado);
                    // tr.appendChild(tdDestino);
                    tr.appendChild(tdAccion);

                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">No se encontraron agentes.</td></tr>`;
            }
        });
    });

    document.getElementById('addProduct').addEventListener('click', () => {
        console.log("Abriendo modal de productos...");
        productModal.show();
    });

    document.getElementById('searchProduct').addEventListener('input', (e) => {
        const searchValue = e.target.value;
        console.log("Buscando productos:", searchValue);

        fetch(`productos/listado?jsonList=true&search=${searchValue}`)
            .then(res => res.json())
            .then(data => {
                console.log("Productos: " + JSON.stringify(data));
                if (data.productos) {
                    const tbody = document.querySelector('#productTable tbody');
                    tbody.innerHTML = ''; // limpiar tabla
                    
                    data.productos.forEach(product => {
                        const tr = document.createElement('tr');
                        const tieneStock = parseFloat(product.stock_actual) > 0;

                        const tdDescripcion = document.createElement('td');
                        tdDescripcion.textContent = product.descripcion_proyecto;

                        const tdPrecio = document.createElement('td');
                        tdPrecio.textContent = `$${parseFloat(product.precio).toFixed(2)}`;

                        const tdStock = document.createElement('td');
                        tdStock.textContent = parseFloat(product.stock_actual).toFixed(2);

                        const tdAccion = document.createElement('td');
                        if (tieneStock) {
                            const btnSelect = document.createElement('button');
                            btnSelect.textContent = "Seleccionar";
                            btnSelect.classList.add('btn', 'btn-sm', 'btn-success');
                            btnSelect.addEventListener('click', () => {
                                addProductToTable(product);
                                productModal.hide();
                            });
                            tdAccion.appendChild(btnSelect);
                        } else {
                            tdAccion.innerHTML = '<span class="text-muted">Sin stock</span>';
                        }

                        tr.appendChild(tdDescripcion);
                        tr.appendChild(tdPrecio);
                        tr.appendChild(tdStock);
                        tr.appendChild(tdAccion);
                        tr.classList.add(tieneStock ? 'table-success' : 'table-danger');

                        tbody.appendChild(tr);
                    });
                }
            });
    });

    document.getElementById('facturaForm').addEventListener('submit', (e) => {
        e.preventDefault(); // Evitar la recarga de la página
    
        // Capturar los datos del formulario
        const formData = new FormData(e.target);
    
        // Capturar el total facturado desde el DOM
        const totalFacturado = document.getElementById('total_facturado').textContent.trim();
        formData.append('total_facturado', totalFacturado);    
    
        // Capturar la cantidad de cuotas (si aplica)
        const selectCuotas = document.getElementById('selectCuotas');
        if (selectCuotas && selectCuotas.value) {
            formData.append('cantidad_cuotas', selectCuotas.value);
        }
    
        // Capturar los productos de la tabla
        let productos = [];
        document.querySelectorAll('#productosTable tbody tr').forEach(row => {
            const cantidad = row.querySelector('.cantidad-producto').value;
            const precioUnitario = row.querySelector('.cantidad-producto').dataset.precio;
            const idProducto = row.querySelector('.cantidad-producto').dataset.id;
    
            productos.push({
                id: idProducto,
                cantidad: cantidad,
                precio_unitario: precioUnitario,
            });
        });
    
        // Agregar los productos al formData como JSON
        formData.append('productos', JSON.stringify(productos));

        if (!validarFormularioFactura(formData, productos)) {
            return; // Detiene el envío si no pasó la validación
        }
        // Enviar la solicitud al backend
        fetch('/facturacion/new', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.info("Factura guardada correctamente. ID: " + data.factura_id);
                window.location.href = '/facturacion/ver?id=' + data.factura_id; // Redirigir a la lista de facturas
            } else {
                mostrarMensajeModal(`Error al guardar la factura: ${data.error}`, 'Error del servidor');
            }
        })
        .catch(error => {
            console.error("Error en la petición:", error);
            mostrarMensajeModal("Error de red o del servidor. Intente nuevamente.", "Error de red");
        });
    });
    
    

    /***
     * FUNCIONES
     */
    function validarFormularioFactura(formData, productos) {
        const nroComprobante = formData.get('nro_comprobante');
        const dependencia = formData.get('dependencia');
        const condicionVenta = formData.get('condicion_venta');
        const condicionImpositiva = formData.get('condicion_impositiva');
        const agente = formData.get('agente');
        const productosEnTabla = productos.length;
    
        if (!nroComprobante) {
            mostrarMensajeModal("Debe completar el número de comprobante.");
            return false;
        }
    
        if (!dependencia) {
            mostrarMensajeModal("Debe seleccionar una dependencia.");
            return false;
        }
    
        if (!condicionVenta) {
            mostrarMensajeModal("Debe seleccionar una condición de venta.");
            return false;
        }
    
        if (!condicionImpositiva) {
            mostrarMensajeModal("Debe seleccionar una condición impositiva.");
            return false;
        }
    
        if (!agente) {
            mostrarMensajeModal("Debe seleccionar un agente.");
            return false;
        }
    
        if (productosEnTabla === 0) {
            mostrarMensajeModal("Debe agregar al menos un producto.");
            return false;
        }
    
        return true;
    }
    
    

    function mostrarMensajeModal(mensaje, titulo = 'Atención') {
        const modal = document.getElementById('mensajeModal');
        const label = document.getElementById('mensajeModalLabel');
    
        // Estilos condicionales
        if (titulo.toLowerCase().includes('error')) {
            label.classList.add('text-danger');
        } else {
            label.classList.remove('text-danger');
        }
    
        label.textContent = titulo;
        document.getElementById('mensajeModalContenido').textContent = mensaje;
    
        const mensajeModal = new bootstrap.Modal(modal);
        mensajeModal.show();
    }
    
    function mostrarMensajeModal(mensaje, titulo = 'Atención') {
        console.log("[Modal]:", mensaje);
    
        document.getElementById('mensajeModalLabel').textContent = titulo;
        document.getElementById('mensajeModalContenido').textContent = mensaje;
    
        const mensajeModal = new bootstrap.Modal(document.getElementById('mensajeModal'));
        mensajeModal.show();
    }
    // Agregar producto a la tabla con cálculo de subtotal
    function addProductToTable(product) {
        console.log("Agregando producto:", product);
        const tbody = document.querySelector('#productosTable tbody');
        const tr = document.createElement('tr');
    
        tr.innerHTML = `
            <td><input type="number" class="form-control cantidad-producto" min="1" value="1" data-id="${product.id_producto}" data-precio="${product.precio}"></td>
            <td><a href="productos/ver?id_producto=${product.id_producto}">${product.descripcion_proyecto}</a></td>
            <td>${product.nro_proyecto_productivo}</td>
            <td class="precio-unitario">${parseFloat(product.precio).toFixed(2)}</td>
            <td class="subtotal">${parseFloat(product.precio).toFixed(2)}</td>
            <td><button class="btn btn-danger btn-sm remove-product">Eliminar</button></td>
        `;
    
        tr.querySelector('.remove-product').addEventListener('click', () => {
            tr.remove();
            updateTotal();
            actualizarCuotas(); // Recalcular cuotas cuando se elimina un producto
        });
    
        tr.querySelector('.cantidad-producto').addEventListener('input', () => {
            updateSubtotal(tr);
            updateTotal();
            actualizarCuotas(); // Recalcular cuotas cuando cambia la cantidad de un producto
        });
    
        tbody.appendChild(tr);
        updateTotal();
        actualizarCuotas(); // Recalcular cuotas cuando se agrega un producto
    }
    // Actualizar subtotal de un producto específico
    function updateSubtotal(row) {
        const cantidadInput = row.querySelector('.cantidad-producto');
        const precioUnitario = parseFloat(cantidadInput.dataset.precio);
        const cantidad = parseInt(cantidadInput.value, 10) || 0;
        const subtotalCell = row.querySelector('.subtotal');
        const subtotal = cantidad * precioUnitario;
        subtotalCell.textContent = subtotal.toFixed(2);
    }
    // Actualizar total facturado sumando todos los subtotales
    function updateTotal() {
        let total = 0;
        document.querySelectorAll('#productosTable tbody tr').forEach(row => {
            const cantidadInput = row.querySelector('.cantidad-producto');
            const precioUnitario = parseFloat(cantidadInput.dataset.precio);
            const cantidad = parseInt(cantidadInput.value, 10) || 0;
            const subtotal = cantidad * precioUnitario;
    
            row.querySelector('.subtotal').textContent = subtotal.toFixed(2);
            total += subtotal;
        });
    
        document.getElementById('total_facturado').textContent = total.toFixed(2);
        actualizarCuotas(); // Recalcular cuotas cuando cambia el total
    }
    function actualizarCuotas() {
        const totalFacturado = parseFloat(document.getElementById('total_facturado').textContent) || 0;
        const selectCuotas = document.getElementById('selectCuotas');
        const cuotasContainer = document.getElementById('cuotasContainer');
        const montoMinimoCuota = 10000;
        const umbralMultiplesCuotas = 20000;
    
        console.log("[INFO] Recalculando cuotas. Total Facturado:", totalFacturado);
    
        // Limpiar opciones
        selectCuotas.innerHTML = '';
    
        // Si no hay facturación, ocultar selector
        if (totalFacturado <= 0) {
            cuotasContainer.classList.add('d-none');
            console.log(`Total Facturado (${totalFacturado}) es cero. No se muestra selector.`);
            return;
        }
    
        // Mostrar el selector
        cuotasContainer.classList.remove('d-none');
    
        // Si el total es menor a 20.000 → 1 sola cuota
        if (totalFacturado < umbralMultiplesCuotas) {
            const option = document.createElement('option');
            option.value = 1;
            option.textContent = `1 cuota de $${totalFacturado.toFixed(2)}`;
            selectCuotas.appendChild(option);
            console.log(`Total Facturado menor a ${umbralMultiplesCuotas}. Solo una cuota.`);
            return;
        }
    
        // Calcular el máximo de cuotas permitidas bajo la condición:
        // cada cuota >= 10.000, excepto tal vez la última
        const maxCuotas = Math.floor(totalFacturado / montoMinimoCuota);
    
        for (let i = 1; i <= maxCuotas; i++) {
            const valorCuota = totalFacturado / i;
            const valorPenultima = totalFacturado / (i - 1); // Para ver si todas son >= 10.000
    
            // Aceptar solo si todas excepto la última son >= $10.000
            if (i === 1 || valorCuota >= montoMinimoCuota || Math.floor(totalFacturado / montoMinimoCuota) === i) {
                const cuotaValue = valorCuota.toFixed(2);
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `${i} cuota(s) de $${cuotaValue}`;
                selectCuotas.appendChild(option);
            } else {
                console.log(`Omitida ${i} cuota(s) porque no cumple condición de mínimo $10.000 por cuota.`);
            }
        }
    }
    
    
    
    
});
