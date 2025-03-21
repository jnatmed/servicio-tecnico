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
            const agentList = document.getElementById('agentList');
            agentList.innerHTML = '';

    
            // Accedemos correctamente a data.agentes
            if (data.success && Array.isArray(data.agentes)) {
                data.agentes.forEach(agent => {
                    const li = document.createElement('li');
                    li.classList.add('list-group-item', 'list-group-item-action');
                    li.textContent = `${agent.nombre} ${agent.apellido}`;
                    li.addEventListener('click', () => {
                        document.getElementById('selectedAgent').textContent = `${agent.nombre} ${agent.apellido}`;
                        document.getElementById('agente').value = agent.id;
                        document.getElementById('destino_agente').textContent = `(Destino: ${agent.dependencia ?? "No especificado"})`;
                        console.log("Agente seleccionado:", agent);
                        agentModal.hide();
                    });
                    agentList.appendChild(li);
                });
            } else {
                console.warn("No se encontraron agentes.");
            }
        })
        .catch(error => console.error("Error en fetch:", error));
    });
    
    // Abrir modal de productos
    document.getElementById('addProduct').addEventListener('click', () => {
        console.log("Abriendo modal de productos...");
        productModal.show();
    });

    // Buscar productos dinámicamente
    document.getElementById('searchProduct').addEventListener('input', (e) => {
        const searchValue = e.target.value;
        console.log("Buscando productos:", searchValue);

        fetch(`productos/listado?jsonList=true&search=${searchValue}`)
            .then(res => res.json())
            .then(data => {
                const productList = document.getElementById('productList');
                productList.innerHTML = '';

                if (data[0]) {
                    data[0].forEach(product => {
                        const li = document.createElement('li');
                        li.classList.add('list-group-item', 'list-group-item-action');
                        li.textContent = `${product.descripcion_proyecto} (${product.precio})`;
                        li.addEventListener('click', () => {
                            addProductToTable(product);
                            productModal.hide();
                        });
                        productList.appendChild(li);
                    });
                }
            });
    });
    
    document.getElementById('facturaForm').addEventListener('submit', (e) => {
        e.preventDefault(); // Evitar la recarga de la página
    
        // Capturar los datos del formulario
        const formData = new FormData(e.target);

        // 🔹 Capturar el total facturado desde el DOM
        const totalFacturado = document.getElementById('total_facturado').textContent.trim();
        formData.append('total_facturado', totalFacturado);    
        
        // Capturar los productos de la tabla
        let productos = [];
        document.querySelectorAll('#productosTable tbody tr').forEach(row => {
            const cantidad = row.querySelector('.cantidad-producto').value;
            const precioUnitario = row.querySelector('.cantidad-producto').dataset.precio;
            const idProducto = row.querySelector('.cantidad-producto').dataset.id;
    
            productos.push({
                id: idProducto,
                cantidad: cantidad,
                precio_unitario: precioUnitario
            });
        });
    
        // Agregar los productos al formData como JSON
        formData.append('productos', JSON.stringify(productos));

        // Mostrar los datos del formulario en consola para depuración
        console.debug("Enviando datos del formulario:", Object.fromEntries(formData.entries()));
    
        // Enviar la solicitud al backend
        fetch('/facturacion/new', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                console.error("Factura guardada correctamente. ID: " + data.factura_id);
                window.location.href = '/facturacion/listar'; // Opcional: Recargar la página o redirigir a otra vista
            } else {
                console.error("Error al guardar la factura: " + data.error);
            }
        })
        .catch(error => {
            console.error("Error en la petición:", error);
            console.error("Hubo un problema al enviar la factura.");
        });
    });
    

    /***
     * FUNCIONES
     */

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
            <td>${product.descripcion_proyecto}</td>
            <td>${product.nro_proyecto_productivo}</td>
            <td class="precio-unitario">${parseFloat(product.precio).toFixed(2)}</td>
            <td class="subtotal">${parseFloat(product.precio).toFixed(2)}</td>
            <td><button class="btn btn-danger btn-sm remove-product">Eliminar</button></td>
        `;
    
        tr.querySelector('.remove-product').addEventListener('click', () => {
            tr.remove();
            updateTotal();
            actualizarCuotas(); // 🔹 Recalcular cuotas cuando se elimina un producto
        });
    
        tr.querySelector('.cantidad-producto').addEventListener('input', () => {
            updateSubtotal(tr);
            updateTotal();
            actualizarCuotas(); // 🔹 Recalcular cuotas cuando cambia la cantidad de un producto
        });
    
        tbody.appendChild(tr);
        updateTotal();
        actualizarCuotas(); // 🔹 Recalcular cuotas cuando se agrega un producto
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
        actualizarCuotas(); // 🔹 Recalcular cuotas cuando cambia el total
    }
    function actualizarCuotas() {
        const totalFacturado = parseFloat(document.getElementById('total_facturado').textContent) || 0;
        const selectCuotas = document.getElementById('selectCuotas');
        const cuotasContainer = document.getElementById('cuotasContainer');
        const montoMinimoCuota = 10000; // Monto mínimo por cuota
    
        console.log("[INFO] Recalculando cuotas. Total Facturado:", totalFacturado);
    
        // Si el total facturado es menor a 10.000, ocultar el selector de cuotas
        if (totalFacturado < montoMinimoCuota) {
            cuotasContainer.classList.add('d-none'); // Ocultar
            selectCuotas.innerHTML = ''; // Limpiar opciones
            return;
        }
    
        // Calcular cantidad de cuotas posibles
        const cantidadCuotas = Math.ceil(totalFacturado / montoMinimoCuota);
    
        // Limpiar y generar nuevas opciones
        selectCuotas.innerHTML = '';
        for (let i = 1; i <= cantidadCuotas; i++) {
            const cuotaValue = (totalFacturado / i).toFixed(2);
            const option = document.createElement('option');
            option.value = i;
            option.textContent = `${i} cuota(s) de $${cuotaValue}`;
            selectCuotas.appendChild(option);
        }
    
        cuotasContainer.classList.remove('d-none'); // Mostrar el selector de cuotas
    }
    
});
