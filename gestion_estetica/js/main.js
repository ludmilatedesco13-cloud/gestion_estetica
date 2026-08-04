document.addEventListener("DOMContentLoaded", function () {


    // 1. máscara y formateo de precios en texto

    const inputsPrecio = document.querySelectorAll(".formato-precio");

    function aplicarFormatoPrecio(input) {
        let valor = input.value.replace(/\D/g, ""); // Deja solo números
        if (valor !== "") {
            input.value = parseInt(valor, 10).toLocaleString("es-AR");
        } else {
            input.value = "";
        }
    }

    inputsPrecio.forEach(input => {
        aplicarFormatoPrecio(input);

        input.addEventListener("input", function () {
            aplicarFormatoPrecio(this);
        });

        input.addEventListener("focus", function () {
            this.value = this.value.replace(/\./g, "");
            this.select();
        });

        input.addEventListener("blur", function () {
            aplicarFormatoPrecio(this);
        });
    });


    // 2. validación de formulario "nuevo producto"
    
    const formNuevoProducto = document.getElementById("form-nuevo-producto");

    if (formNuevoProducto) {
        formNuevoProducto.addEventListener("submit", function (e) {
            const inputCosto = document.getElementById("precio_costo");
            const inputVenta = document.getElementById("precio_venta");

            const valorCostoLimpio = inputCosto ? inputCosto.value.replace(/\./g, "").replace(",", ".") : "0";
            const valorVentaLimpio = inputVenta ? inputVenta.value.replace(/\./g, "").replace(",", ".") : "0";

            const precioCosto = parseFloat(valorCostoLimpio) || 0;
            const precioVenta = parseFloat(valorVentaLimpio) || 0;

            // Validación: Si Venta < Costo
            if (precioVenta < precioCosto) {
                e.preventDefault();

                Swal.fire({
                    title: '¿Confirmar precio de venta?',
                    text: `El precio de venta ($${precioVenta.toLocaleString('es-AR')}) es MENOR que el precio de costo ($${precioCosto.toLocaleString('es-AR')}). ¿Desea guardarlo de todas formas?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dca397',
                    cancelButtonColor: '#7d6660',
                    confirmButtonText: 'Sí, guardar',
                    cancelButtonText: 'Corregir'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (inputCosto) inputCosto.value = valorCostoLimpio;
                        if (inputVenta) inputVenta.value = valorVentaLimpio;
                        formNuevoProducto.submit();
                    }
                });
            } else {
                // Si la validación pasa, limpiamos los puntos antes del POST
                if (inputCosto) inputCosto.value = valorCostoLimpio;
                if (inputVenta) inputVenta.value = valorVentaLimpio;
            }
        });
    }


    // 3. carrito de ventas dinámico(datalist)
    const inputBuscar = document.getElementById("producto_buscar");
    const inputCantidad = document.getElementById("cantidad");
    const btnAgregarItem = document.getElementById("btn-agregar-item");
    const tablaCarrito = document.getElementById("tabla-carrito");
    const totalVentaElem = document.getElementById("monto-total-venta");
    const jsonProductosInput = document.getElementById("json_productos_venta");
    const datalist = document.getElementById("lista_productos");

    let carrito = [];

    if (btnAgregarItem && inputBuscar && datalist) {
        btnAgregarItem.addEventListener("click", function () {
            const valBusqueda = inputBuscar.value.trim();
            const cantidad = parseInt(inputCantidad.value, 10);

            if (!valBusqueda || isNaN(cantidad) || cantidad <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención ⚠️',
                    text: 'Por favor, seleccione un producto válido y una cantidad mayor a 0.',
                    confirmButtonColor: '#dca397'
                });
                return;
            }

            // Buscar el option dentro del datalist que coincida con el valor ingresado
            let optionEncontrada = null;
            Array.from(datalist.options).forEach(opt => {
                if (opt.value === valBusqueda) {
                    optionEncontrada = opt;
                }
            });

            if (!optionEncontrada) {
                Swal.fire({
                    icon: 'error',
                    title: 'Producto no encontrado ❌',
                    text: 'Seleccione un producto de la lista desplegable.',
                    confirmButtonColor: '#dca397'
                });
                return;
            }

            const prodId = optionEncontrada.dataset.id;
            const stockMax = parseInt(optionEncontrada.dataset.stock, 10);
            const precio = parseFloat(optionEncontrada.dataset.precio);
            const nombre = valBusqueda;

            // Control de Stock Acumulado
            const itemExistente = carrito.find(item => item.id === prodId);
            const cantidadExistente = itemExistente ? itemExistente.cantidad : 0;
            const cantidadTotalAcumulada = cantidadExistente + cantidad;

            if (cantidadTotalAcumulada > stockMax) {
                Swal.fire({
                    icon: 'error',
                    title: 'Stock insuficiente 📦',
                    text: `Solo quedan ${stockMax} unidades disponibles. Ya agregaste ${cantidadExistente} al carrito.`,
                    confirmButtonColor: '#dca397'
                });
                return;
            }

            // Agregar o actualizar en el carrito
            if (itemExistente) {
                itemExistente.cantidad = cantidadTotalAcumulada;
                itemExistente.subtotal = itemExistente.cantidad * precio;
            } else {
                carrito.push({
                    id: prodId,
                    nombre: nombre,
                    cantidad: cantidad,
                    precio: precio,
                    subtotal: cantidad * precio
                });
            }

            renderizarCarrito();

            // Limpiar campos
            inputBuscar.value = "";
            inputCantidad.value = "1";
        });
    }

    function renderizarCarrito() {
        const tbody = tablaCarrito.querySelector("tbody");
        tbody.innerHTML = "";
        let total = 0;

        carrito.forEach((item, index) => {
            total += item.subtotal;
            const fila = document.createElement("tr");

            fila.innerHTML = `
                <td><strong>${item.nombre}</strong></td>
                <td>$${item.precio.toLocaleString('es-AR')}</td>
                <td>${item.cantidad}</td>
                <td>$${item.subtotal.toLocaleString('es-AR')}</td>
                <td>
                    <button type="button" class="btn-icon" style="background:none; border:none; color:#dca397; cursor:pointer;" title="Quitar">🗑️</button>
                </td>
            `;

            // Botón eliminar fila
            fila.querySelector("button").addEventListener("click", function () {
                carrito.splice(index, 1);
                renderizarCarrito();
            });

            tbody.appendChild(fila);
        });

        // Actualizar vista total y campo oculto JSON para PHP
        totalVentaElem.textContent = total.toLocaleString("es-AR");
        if (jsonProductosInput) {
            jsonProductosInput.value = JSON.stringify(carrito);
        }
    }


   
    // 4. lupita 🔎
   
    const modal = document.getElementById("modal-detalles");
    const btnCerrarModal = document.querySelector(".close-modal");

    document.addEventListener("click", function (e) {
        const btnVer = e.target.closest(".btn-ver");

        if (btnVer && modal) {
            e.preventDefault();

            // Capturar datos desde los data-attributes de la tabla
            document.getElementById("modal-titulo-prod").textContent = btnVer.dataset.nombre || "Sin Nombre";
            document.getElementById("modal-marca-prod").textContent = btnVer.dataset.marca || "No especificada";
            document.getElementById("modal-precio-prod").textContent = btnVer.dataset.precio || "0";
            document.getElementById("modal-stock-prod").textContent = btnVer.dataset.stock || "0";
            document.getElementById("modal-info-prod").textContent = btnVer.dataset.info || "Sin descripción.";
            document.getElementById("modal-img-prod").src = btnVer.dataset.foto || "img/producto_defecto.jpg";

            modal.style.display = "flex";
        }
    });

    if (btnCerrarModal && modal) {
        btnCerrarModal.addEventListener("click", function () {
            modal.style.display = "none";
        });

        window.addEventListener("click", function (e) {
            if (e.target === modal) {
                modal.style.display = "none";
            }
        });
    }

});