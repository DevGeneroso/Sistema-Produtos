document.addEventListener("DOMContentLoaded", () => {
  // Global variables
  let currentPage = 1;
  const itemsPerPage = 10;
  let totalPages = 1;
  let searchTimeout = null;

  // Load products on page load
  loadProducts();

  // Event listeners
  document.getElementById("filterForm").addEventListener("submit", (e) => {
    e.preventDefault();
    currentPage = 1;
    loadProducts();
  });

  // Adiciona evento de input para busca em tempo real
  document.getElementById("filterName").addEventListener("input", () => {
    // Limpa o timeout anterior para evitar múltiplas requisições
    if (searchTimeout) {
      clearTimeout(searchTimeout);
    }

    // Define um novo timeout para executar a busca após 300ms
    searchTimeout = setTimeout(() => {
      currentPage = 1;
      loadProducts();
    }, 300); // 300ms de delay para evitar muitas requisições enquanto digita
  });

  document.getElementById("filterStatus").addEventListener("change", () => {
    currentPage = 1;
    loadProducts();
  });

  document.getElementById("clearFilters").addEventListener("click", () => {
    document.getElementById("filterName").value = "";
    document.getElementById("filterStatus").value = "";
    currentPage = 1;
    loadProducts();
  });

  document
    .getElementById("saveProductBtn")
    .addEventListener("click", saveProduct);
  document
    .getElementById("updateProductBtn")
    .addEventListener("click", updateProduct);
  document
    .getElementById("confirmDeleteBtn")
    .addEventListener("click", deleteProduct);

  // Check product code availability on input
  document.getElementById("productCode").addEventListener("blur", function () {
    const code = this.value.trim();
    if (code) {
      checkProductCode(code);
    }
  });

  // Functions
  function loadProducts() {
    const name = document.getElementById("filterName").value;
    const status = document.getElementById("filterStatus").value;

    let url = `api/products.php?page=${currentPage}&limit=${itemsPerPage}`;

    if (name) {
      url += `&name=${encodeURIComponent(name)}`;
    }

    if (status !== "") {
      url += `&status=${status}`;
    }

    fetch(url)
      .then((response) => response.json())
      .then((data) => {
        if (data.error) {
          showError(data.error);
          return;
        }

        renderProducts(data.products);
        renderPagination(data.pagination);
      })
      .catch((error) => {
        showError("Erro ao carregar produtos: " + error);
      });
  }

  function renderProducts(products) {
    const tableBody = document.getElementById("productsTableBody");
    tableBody.innerHTML = "";

    if (products.length === 0) {
      const row = document.createElement("tr");
      row.innerHTML =
        '<td colspan="7" class="text-center">Nenhum produto encontrado</td>';
      tableBody.appendChild(row);
      return;
    }

    products.forEach((product) => {
      const row = document.createElement("tr");
      row.innerHTML = `
        <td>${product.code}</td>
        <td>${product.name}</td>
        <td>${product.description || "-"}</td>
        <td>R$ ${Number.parseFloat(product.price).toFixed(2)}</td>
        <td>${product.quantity}</td>
        <td>
          <span class="badge ${
            product.status == 1 ? "bg-success" : "bg-danger"
          }">
            ${product.status == 1 ? "Ativo" : "Inativo"}
          </span>
        </td>
        <td>
          <button class="btn btn-sm btn-primary edit-btn" data-id="${
            product.id
          }">
            <i class="bi bi-pencil"></i>
          </button>
          <button class="btn btn-sm btn-danger delete-btn" data-id="${
            product.id
          }">
            <i class="bi bi-trash"></i>
          </button>
        </td>
      `;
      tableBody.appendChild(row);
    });

    // Add event listeners to edit and delete buttons
    document.querySelectorAll(".edit-btn").forEach((button) => {
      button.addEventListener("click", function () {
        const productId = this.getAttribute("data-id");
        openEditModal(productId);
      });
    });

    document.querySelectorAll(".delete-btn").forEach((button) => {
      button.addEventListener("click", function () {
        const productId = this.getAttribute("data-id");
        openDeleteModal(productId);
      });
    });
  }

  function renderPagination(pagination) {
    const paginationElement = document.getElementById("pagination");
    paginationElement.innerHTML = "";

    totalPages = pagination.totalPages;

    // Previous button
    const prevLi = document.createElement("li");
    prevLi.className = `page-item ${currentPage === 1 ? "disabled" : ""}`;
    prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous">
                          <span aria-hidden="true">&laquo;</span>
                        </a>`;
    paginationElement.appendChild(prevLi);

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
      const li = document.createElement("li");
      li.className = `page-item ${i === currentPage ? "active" : ""}`;
      li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
      paginationElement.appendChild(li);
    }

    // Next button
    const nextLi = document.createElement("li");
    nextLi.className = `page-item ${
      currentPage === totalPages ? "disabled" : ""
    }`;
    nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next">
                          <span aria-hidden="true">&raquo;</span>
                        </a>`;
    paginationElement.appendChild(nextLi);

    // Add event listeners to pagination buttons
    document.querySelectorAll(".page-link").forEach((link, index) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();

        if (index === 0) {
          // Previous button
          if (currentPage > 1) {
            currentPage--;
            loadProducts();
          }
        } else if (index === totalPages + 1) {
          // Next button
          if (currentPage < totalPages) {
            currentPage++;
            loadProducts();
          }
        } else {
          // Page number
          currentPage = index;
          loadProducts();
        }
      });
    });
  }

  function checkProductCode(code) {
    fetch(`api/products.php?page=1&limit=1&code=${encodeURIComponent(code)}`)
      .then((response) => response.json())
      .then((data) => {
        const codeInput = document.getElementById("productCode");
        if (data.products && data.products.length > 0) {
          codeInput.classList.add("is-invalid");
        } else {
          codeInput.classList.remove("is-invalid");
        }
      })
      .catch((error) => {
        console.error("Erro ao verificar código:", error);
      });
  }

  function saveProduct() {
    const code = document.getElementById("productCode").value.trim();
    const name = document.getElementById("productName").value.trim();
    const description = document
      .getElementById("productDescription")
      .value.trim();
    const price =
      Number.parseFloat(document.getElementById("productPrice").value) || 0;
    const quantity =
      Number.parseInt(document.getElementById("productQuantity").value) || 0;
    const status = document.getElementById("productStatus").value;

    // Validate required fields
    if (!code || !name) {
      showError("Código e nome do produto são obrigatórios");
      return;
    }

    const productData = {
      code: code,
      name: name,
      description: description,
      price: price,
      quantity: quantity,
      status: status,
    };

    fetch("api/products.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(productData),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.error) {
          showError(data.error);
          return;
        }

        // Close modal and reset form
        const addProductModal = document.getElementById("addProductModal");
        // Get the Bootstrap modal instance
        const bsModal = bootstrap.Modal.getInstance(addProductModal);
        if (bsModal) {
          bsModal.hide();
        }
        document.getElementById("addProductForm").reset();

        // Reload products
        loadProducts();
      })
      .catch((error) => {
        showError("Erro ao salvar produto: " + error);
      });
  }

  function openEditModal(productId) {
    fetch(`api/products.php?id=${productId}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.error) {
          showError(data.error);
          return;
        }

        const product = data.products[0];

        document.getElementById("editProductId").value = product.id;
        document.getElementById("editProductCode").value = product.code;
        document.getElementById("editProductName").value = product.name;
        document.getElementById("editProductDescription").value =
          product.description;
        document.getElementById("editProductPrice").value = product.price;
        document.getElementById("editProductQuantity").value = product.quantity;
        document.getElementById("editProductStatus").value = product.status;

        const modal = new bootstrap.Modal(
          document.getElementById("editProductModal")
        );
        modal.show();
      })
      .catch((error) => {
        showError("Erro ao carregar produto: " + error);
      });
  }

  function updateProduct() {
    const id = document.getElementById("editProductId").value;
    const name = document.getElementById("editProductName").value.trim();
    const description = document
      .getElementById("editProductDescription")
      .value.trim();
    const price =
      Number.parseFloat(document.getElementById("editProductPrice").value) || 0;
    const quantity =
      Number.parseInt(document.getElementById("editProductQuantity").value) ||
      0;
    const status = document.getElementById("editProductStatus").value;

    // Validate required fields
    if (!name) {
      showError("Nome do produto é obrigatório");
      return;
    }

    const productData = {
      id: id,
      name: name,
      description: description,
      price: price,
      quantity: quantity,
      status: status,
    };

    fetch("api/products.php", {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(productData),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.error) {
          showError(data.error);
          return;
        }

        // Close modal
        const editProductModal = document.getElementById("editProductModal");
        const bsModal = bootstrap.Modal.getInstance(editProductModal);
        if (bsModal) {
          bsModal.hide();
        }

        // Reload products
        loadProducts();
      })
      .catch((error) => {
        showError("Erro ao atualizar produto: " + error);
      });
  }

  function openDeleteModal(productId) {
    document.getElementById("deleteProductId").value = productId;
    const modal = new bootstrap.Modal(
      document.getElementById("deleteProductModal")
    );
    modal.show();
  }

  function deleteProduct() {
    const id = document.getElementById("deleteProductId").value;

    fetch("api/products.php", {
      method: "DELETE",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ id: id }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.error) {
          showError(data.error);
          return;
        }

        // Close modal
        const deleteProductModal =
          document.getElementById("deleteProductModal");
        const bsModal = bootstrap.Modal.getInstance(deleteProductModal);
        if (bsModal) {
          bsModal.hide();
        }

        // Reload products
        loadProducts();
      })
      .catch((error) => {
        showError("Erro ao excluir produto: " + error);
      });
  }

  function showError(message) {
    document.getElementById("errorMessage").textContent = message;
    const modal = new bootstrap.Modal(document.getElementById("errorModal"));
    modal.show();
  }

  // Initialize Bootstrap modals (if not already initialized)
  const addProductModalEl = document.getElementById("addProductModal");
  if (addProductModalEl) {
    const addProductModal = new bootstrap.Modal(addProductModalEl);
  }

  const editProductModalEl = document.getElementById("editProductModal");
  if (editProductModalEl) {
    const editProductModal = new bootstrap.Modal(editProductModalEl);
  }

  const deleteProductModalEl = document.getElementById("deleteProductModal");
  if (deleteProductModalEl) {
    const deleteProductModal = new bootstrap.Modal(deleteProductModalEl);
  }

  const errorModalEl = document.getElementById("errorModal");
  if (errorModalEl) {
    const errorModal = new bootstrap.Modal(errorModalEl);
  }
});
