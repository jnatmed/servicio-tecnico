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

                data.forEach(agent => {
                    const li = document.createElement('li');
                    li.classList.add('list-group-item', 'list-group-item-action');
                    li.textContent = `${agent.nombre} ${agent.apellido}`;
                    li.addEventListener('click', () => {
                        document.getElementById('selectedAgent').textContent = `${agent.nombre} ${agent.apellido}`;
                        document.getElementById('agente').value = agent.id;
                        console.log("Agente seleccionado:", agent);
                        agentModal.hide();
                    });
                    agentList.appendChild(li);
                });
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

        fetch(`api_get_productos?search=${searchValue}`)
            .then(res => res.json())
            .then(data => {
                const productList = document.getElementById('productList');
                productList.innerHTML = '';

                data.forEach(product => {
                    const li = document.createElement('li');
                    li.classList.add('list-group-item', 'list-group-item-action');
                    li.textContent = `${product.descripcion} (${product.stock})`;
                    li.addEventListener('click', () => {
                        addProductToTable(product);
                        productModal.hide();
                    });
                    productList.appendChild(li);
                });
            });
    });

    // Agregar producto a la tabla
    function addProductToTable(product) {
        console.log("Agregando producto:", product);
        const tbody = document.querySelector('#productosTable tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${product.descripcion}</td>
            <td><input type="number" class="form-control cantidad-producto" min="1" value="1" data-id="${product.id}"></td>
            <td><button class="btn btn-danger btn-sm remove-product">Eliminar</button></td>
        `;
        tr.querySelector('.remove-product').addEventListener('click', () => {
            tr.remove();
            updateTotal();
        });
        tbody.appendChild(tr);
        updateTotal();
    }

    // Actualizar total
    function updateTotal() {
        const cantidades = document.querySelectorAll('.cantidad-producto');
        let total = 0;

        cantidades.forEach(input => {
            const productId = input.getAttribute('data-id');
            const cantidad = parseInt(input.value, 10) || 0;

            fetch(`api_get_precio_producto?id=${productId}`)
                .then(res => res.json())
                .then(data => {
                    total += data.precio * cantidad;
                    document.getElementById('total_facturado').textContent = total.toFixed(2);
                });
        });
    }
});
