document.addEventListener('DOMContentLoaded', () => {
    // Inicializar modales
    const agentModal = new bootstrap.Modal(document.getElementById('agentModal'));
    const productModal = new bootstrap.Modal(document.getElementById('productModal'));

    // Abrir modal de agentes
    document.getElementById('openAgentModal').addEventListener('click', () => {
        console.log("Abriendo modal de agentes...");
        agentModal.show();
    });

    // Buscar agentes dinámicamente
    document.getElementById('searchAgent').addEventListener('input', (e) => {
        const searchValue = e.target.value;
        console.log("Buscando agentes:", searchValue);

        fetch(`api_get_agentes?search=${searchValue}`)
            .then(res => res.json())
            .then(data => {
                const agentList = document.getElementById('agentList');
                agentList.innerHTML = '';

                if (data[0]) {
                    data[0].forEach(agent => {
                        const li = document.createElement('li');
                        li.classList.add('list-group-item', 'list-group-item-action');
                        li.textContent = `${agent.nombre} ${agent.apellido}`;
                        li.addEventListener('click', () => {
                            document.getElementById('selectedAgent').textContent = `${agent.nombre} ${agent.apellido}`;
                            document.getElementById('agente').value = agent.id;
                            document.getElementById('destino_agente').textContent = `(Destino: ${agent.descripcion_dependencia})`;
                            console.log("Agente seleccionado:", agent);
                            agentModal.hide();
                        });
                        agentList.appendChild(li);
                    });
                }
            });
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

        // Evento para eliminar producto y actualizar total
        tr.querySelector('.remove-product').addEventListener('click', () => {
            tr.remove();
            updateTotal();
        });

        // Evento para actualizar subtotal cuando cambia la cantidad
        tr.querySelector('.cantidad-producto').addEventListener('input', () => {
            updateSubtotal(tr);
            updateTotal();
        });

        tbody.appendChild(tr);
        updateTotal();
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
    }
});
