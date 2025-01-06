document.addEventListener('DOMContentLoaded', function () {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');

    if (dropZone && fileInput) {
        // Manejo del clic en la zona de drop
        dropZone.addEventListener('click', () => fileInput.click());

        // Manejo del evento dragover
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        // Manejo del evento dragleave
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        // Manejo del evento drop
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                dropZone.textContent = files[0].name;
            } else {
                console.warn('No se detectaron archivos en el evento de drop.');
            }
        });

        // Manejo del cambio en el input de archivos
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                dropZone.textContent = fileInput.files[0].name;
            } else {
                console.warn('El input de archivos está vacío después del cambio.');
            }
        });
    } else {
        if (!dropZone) {
            console.error('No se encontró el elemento con ID "drop-zone" en el DOM.');
        }
        if (!fileInput) {
            console.error('No se encontró el elemento con ID "file-input" en el DOM.');
        }
    }
});
