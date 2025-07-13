// Pagination functionality

const totalItems = 2600;
let currentPage = 1;
let rowsPerPage = 25;

const rowsSelect = document.getElementById('rowsPerPage');
const rangeDisplay = document.getElementById('rangeDisplay');
const pagesContainer = document.getElementById('pages');
const prevBtn = document.getElementById('prevPage');
const nextBtn = document.getElementById('nextPage');

function updatePagination() {
   const totalPages = Math.ceil(totalItems / rowsPerPage);
   const startItem = (currentPage - 1) * rowsPerPage + 1;
   const endItem = Math.min(currentPage * rowsPerPage, totalItems);
   rangeDisplay.textContent = `${startItem}–${endItem} з ${totalItems}`;
 
   pagesContainer.innerHTML = '';
 
   const maxVisiblePages = 5;
   let startPage = Math.max(currentPage - Math.floor(maxVisiblePages / 2), 1);
   let endPage = startPage + maxVisiblePages - 1;
 
   if (endPage > totalPages) {
     endPage = totalPages;
     startPage = Math.max(endPage - maxVisiblePages + 1, 1);
   }
 
   for (let i = startPage; i <= endPage; i++) {
     const btn = document.createElement('button');
     btn.textContent = i;
     btn.className = 'page-button' + (i === currentPage ? ' active' : '');
     btn.addEventListener('click', () => {
       currentPage = i;
       updatePagination();
     });
     pagesContainer.appendChild(btn);
   }
 
   prevBtn.disabled = currentPage === 1;
   nextBtn.disabled = currentPage === totalPages;
 }
 

rowsSelect.addEventListener('change', () => {
  rowsPerPage = parseInt(rowsSelect.value);
  currentPage = 1;
  updatePagination();
});

prevBtn.addEventListener('click', () => {
  if (currentPage > 1) {
    currentPage--;
    updatePagination();
  }
});

nextBtn.addEventListener('click', () => {
  const totalPages = Math.ceil(totalItems / rowsPerPage);
  if (currentPage < totalPages) {
    currentPage++;
    updatePagination();
  }
});


updatePagination();


// order status modal functionality
document.querySelectorAll(".close-order-modal-btn").forEach(btn => {
  btn.addEventListener("click", () => {
    document.querySelector(".order-modal-backdrop").style.display = "none";
  });
});

const submitBtn = document.querySelector(".order-submit-btn");
const backdrop = document.querySelector(".order-modal-backdrop");

if (submitBtn && backdrop) {
  submitBtn.addEventListener("click", () => {
    backdrop.style.display = "flex";
  });
}


// map and photo  modal functionality
document.querySelectorAll(".close-map-modal-btn").forEach(btn => {
  btn.addEventListener("click", () => {
    document.querySelector(".map-modal-backdrop").style.display = "none";
  });
});

const showMapModalBtn = document.querySelector(".map-btn");
const showPhotoModalBtn = document.querySelector(".construction-photo");
const mapBackdrop = document.querySelector(".map-modal-backdrop");
const mapWidjet = document.querySelector(".modal-map.map-widget");
const modalProductPhoto = document.querySelector(".modal-map.modal-photo");


if (showMapModalBtn && mapBackdrop) {
  showMapModalBtn.addEventListener("click", () => {
    mapWidjet.style.display = "block";
    mapBackdrop.style.display = "flex";
    modalProductPhoto.style.display = "none";

  });
}

if (showPhotoModalBtn && mapBackdrop) {
  showPhotoModalBtn.addEventListener("click", () => {
    mapWidjet.style.display = "none";
    mapBackdrop.style.display = "flex";
    modalProductPhoto.style.display = "flex";
  });
}

// if mobile table checked - change bg color
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".constructions-table-mobile.select-button").forEach(function (button) {
    button.addEventListener("click", function (event) {
      // Находим ближайший родительский <table>
      const table = button.closest("table.constructions-table-mobile");

      if (table) {
        table.classList.toggle("checked");
      }
    });
  });
});

// if desktop row checked - change bg color
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".constructions-table .select-button").forEach(function (button) {
    button.addEventListener("click", function () {
      const row = button.closest("tr");
      if (row) {
        row.classList.toggle("checked");
      }
    });
  });
});