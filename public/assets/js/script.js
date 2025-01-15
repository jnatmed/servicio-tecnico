// Modal de agentes
const agentModal = new bootstrap.Modal(document.getElementById('agentModal')); // Usar el componente de Bootstrap
const openAgentModal = document.getElementById('openAgentModal');
const agentList = document.getElementById('agentList');
const selectedAgent = document.getElementById('selectedAgent');

// Abrir el modal de agentes
openAgentModal.addEventListener('click', () => agentModal.show());

// Buscar agentes dinámicamente
document.getElementById('searchAgent').addEventListener('input', (e) => {
    const searchValue = e.target.value;
    fetch(`api_get_agentes?search=${searchValue}`)
        .then(res => res.json())
        .then(data => {
            agentList.innerHTML = ''; // Limpiar la lista antes de actualizar
            data.forEach(agent => {
                const li = document.createElement('li');
                li.classList.add('list-group-item', 'list-group-item-action'); // Estilo de Bootstrap
                li.textContent = `${agent.nombre} ${agent.apellido}`;
                li.addEventListener('click', () => {
                    selectedAgent.textContent = `${agent.nombre} ${agent.apellido}`;
                    document.getElementById('agente').value = agent.id;
                    agentModal.hide(); // Cerrar el modal
                });
                agentList.appendChild(li);
            });
        });
});

// Modal de productos
const productModal = new bootstrap.Modal(document.getElementById('productModal')); // Usar el componente de Bootstrap
const addProductButton = document.getElementById('addProduct');
const productList = document.getElementById('productList');
const productosTableBody = document.querySelector('#productosTable tbody');

// Abrir el modal de productos
addProductButton.addEventListener('click', () => productModal.show());

// Buscar productos dinámicamente
document.getElementById('searchProduct').addEventListener('input', (e) => {
    const searchValue = e.target.value;
    fetch(`api_get_productos?search=${searchValue}`)
        .then(res => res.json())
        .then(data => {
            productList.innerHTML = ''; // Limpiar la lista antes de actualizar
            data.forEach(product => {
                const li = document.createElement('li');
                li.classList.add('list-group-item', 'list-group-item-action'); // Estilo de Bootstrap
                li.textContent = `${product.descripcion} (${product.stock})`;
                li.addEventListener('click', () => {
                    addProductToTable(product);
                    productModal.hide(); // Cerrar el modal
                });
                productList.appendChild(li);
            });
        });
});

// Agregar producto a la tabla
function addProductToTable(product) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>${product.descripcion}</td>
        <td><input type="number" class="form-control cantidad-producto" min="1" value="1" data-id="${product.id}"></td>
        <td><button class="btn btn-danger btn-sm remove-product">Eliminar</button></td>
    `;

    // Botón para eliminar producto
    tr.querySelector('.remove-product').addEventListener('click', () => {
        tr.remove();
        updateTotal();
    });

    productosTableBody.appendChild(tr);
    updateTotal(); // Recalcular el total
}

// Actualizar el total facturado
function updateTotal() {
    const cantidades = document.querySelectorAll('.cantidad-producto');
    let total = 0;

    cantidades.forEach(input => {
        const productId = input.getAttribute('data-id');
        const cantidad = parseInt(input.value, 10) || 0;

        // Simular un fetch para obtener el precio del producto por su ID
        fetch(`api_get_precio_producto?id=${productId}`)
            .then(res => res.json())
            .then(data => {
                total += data.precio * cantidad;
                document.getElementById('total_facturado').textContent = total.toFixed(2);
            });
    });
}
