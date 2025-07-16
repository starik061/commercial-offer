@include('add.head')
<script>
  const boards = @json($boards);
  const userRole = @json(Auth::check() ? Auth::user()->role_id : null);
  const isOm = @json(request()->get('om') === 'true');
  let selectedIds = new Set();

  document.addEventListener("DOMContentLoaded", () => {
    // ------------------ ПАГИНАЦИЯ И ДИНАМИЧЕСКАЯ ГЕНЕРАЦИЯ ТАБЛИЦ ------------------
    // Получаем элементы пагинации
    const rowsPerPageSelect = document.getElementById("rowsPerPage");
    const rangeDisplay = document.getElementById("rangeDisplay");
    const pagesContainer = document.getElementById("pages");
    const prevPageBtn = document.getElementById("prevPage");
    const nextPageBtn = document.getElementById("nextPage");

    // Десктопный tbody
    const tbody = document.getElementById("board-tbody");

    // Контейнер для мобильных таблиц
    const mobileTablesContainer = document.getElementById("mobile-tables-container");

    // Начальные значения для пагинации
    let rowsPerPage = parseInt(rowsPerPageSelect.value);
    let currentPage = 1;

    // Обработчики событий для элементов пагинации
    rowsPerPageSelect.addEventListener('change', () => {
      rowsPerPage = parseInt(rowsPerPageSelect.value);
      currentPage = 1;
      renderPage(currentPage);
    });

    prevPageBtn.addEventListener('click', () => {
      if (currentPage > 1) {
        currentPage--;
        renderPage(currentPage);
      }
    });

    nextPageBtn.addEventListener('click', () => {
      const totalPages = Math.ceil(boards.length / rowsPerPage);
      if (currentPage < totalPages) {
        currentPage++;
        renderPage(currentPage);
      }
    });

    // Функция для рендеринга и десктопной, и мобильной версии
    function renderPage(page) {
      // Вычисляем диапазон элементов для текущей страницы
      const start = (page - 1) * rowsPerPage;
      const pageData = boards.slice(start, start + rowsPerPage);

      // Рендерим десктопную таблицу
      renderDesktopTable(pageData);

      // Рендерим мобильные таблицы
      renderMobileTables(pageData);

      // Обновляем контролы пагинации
      updatePaginationControls();
      updateRangeDisplay(start, pageData.length);

      // Добавляем обработчики событий к новым элементам
      addEventListeners();

      LIST_V_busy_calendar();
      showOnMapFunc();
    }

    // Функция для рендеринга десктопной таблицы
    function renderDesktopTable(pageData) {
      tbody.innerHTML = '';
      pageData.forEach(board => tbody.innerHTML += renderBoardRow(board));
    }

    // Функция для рендеринга мобильных таблиц
    function renderMobileTables(pageData) {
      // Удаляем существующие мобильные таблицы
      mobileTablesContainer.innerHTML = '';

      // Создаем новые мобильные таблицы для каждого элемента на странице
      pageData.forEach(board => {
        const mobileTable = createMobileTable(board);
        mobileTablesContainer.appendChild(mobileTable);
      });
    }

    // Функция для создания мобильной таблицы для одного элемента
    function createMobileTable(board) {
      const table = document.createElement('table');
      table.className = 'constructions-table-mobile';

      // Проверяем, выбран ли элемент
      const isSelected = selectedIds.has(String(board.id));
      if (isSelected) {
        table.classList.add('checked');
      }

      const cityPart = board.city_name && (!userRole || userRole != 1) ? board.city_name : '';
      const formatPart = board.format ? (cityPart ? ', ' : '') + board.format : '';
      const addrPart = board.addr ? ((cityPart || formatPart) ? ', ' : '') + board.addr : '';
      const fullAddress = cityPart + formatPart + addrPart || '-';

      table.innerHTML = `
        <tbody class="tbody">
          <tr>
            <td class="mobile-table-head">
              <div>
                <span>код</span>
              </div>
            </td>
            <td class="mobile-table-info">
              <div class="td-code" data-code="${board.id}" data-select-month="">
                ${isOm ? board.code : `<a href="/${board.aleas}" target="_blank">${board.id}</a>`}
              </div>
            </td>
          </tr>

          <tr>
            <td class="mobile-table-head">
              <div>
                <span>тип</span>
              </div>
            </td>
            <td class="mobile-table-info">
              ${isOm ? board.board_type : `<a href="/${board.aleas}" target="_blank">${board.board_type}</a>`}
            </td>
          </tr>

          <tr>
            <td class="mobile-table-head">
              <div>
                <span>адреса</span>
              </div>
            </td>
            <td class="mobile-table-info">
              ${isOm ? fullAddress : `<a href="/${board.aleas}" target="_blank">${fullAddress}</a>`}
            </td>
          </tr>

          <tr>
            <td class="mobile-table-head">
              <div>
                <span>сторона</span>
              </div>
            </td>
            <td class="mobile-table-info">
              ${isOm ? board.side_type : `<a href="/${board.aleas}" target="_blank">${board.side_type}</a>`}
            </td>
          </tr>

          <tr>
            <td class="mobile-table-head">
              <div>
                <span>підсвітка</span>
              </div>
            </td>
            <td class="mobile-table-info">
              <svg class="lighting" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                  viewBox="0 0 18 18" fill="none">
                <path d="M6.75 13.5H11.25" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" ${board.light == 1 ? 'stroke="#4FB14B"' : 'stroke="#ADB0B9"'}/>
                <path d="M7.5 16.5H10.5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" ${board.light == 1 ? 'stroke="#4FB14B"' : 'stroke="#ADB0B9"'}/>
                <path
                    d="M11.3175 10.5C11.4525 9.765 11.805 9.195 12.375 8.625C12.7371 8.29163 13.0246 7.88537 13.2185 7.43294C13.4124 6.98051 13.5083 6.49216 13.5 6C13.5 4.80653 13.0259 3.66193 12.182 2.81802C11.3381 1.97411 10.1935 1.5 9 1.5C7.80653 1.5 6.66193 1.97411 5.81802 2.81802C4.97411 3.66193 4.5 4.80653 4.5 6C4.5 6.75 4.6725 7.6725 5.625 8.625C6.16804 9.12155 6.5385 9.77839 6.6825 10.5"
                    stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" ${board.light == 1 ? 'stroke="#4FB14B"' : 'stroke="#ADB0B9"'}/>
            </svg>
          </td>
        </tr>
        
        <tr>
          <td class="mobile-table-head">
            <div>
              <span>фото</span>
            </div>
          </td>
          <td class="mobile-table-info">
            <div class="construction-photo-wrapper-mobile">
              ${(board.image || board.scheme) ? `
                <img class="construction-photo"
                width="66" height="48"     
                src="${board.image ? `/upload/${board.image}` : (board.scheme ? `/upload/${board.scheme}` : "")}"
                    alt="Фото конструкції">

                <div class="zoom-icon-wrapper">
                  <svg class="zoom-icon img" xmlns="http://www.w3.org/2000/svg" width="8" height="8"
                      data-alias="ua/${board.aleas}"
                      data-id="${board.id}"
                      data-img="${board.image ? `/upload/${board.image}|` : ""}${board.scheme ? `/upload/${board.scheme}` : ""}"
                      viewBox="0 0 8 8" fill="none">
                    <path
                        d="M3.65918 0.250122C5.54196 0.250135 7.06836 1.77652 7.06836 3.6593C7.06833 4.51315 6.75311 5.29265 6.23438 5.89075L7.70312 7.54211L7.23633 7.95813L5.7832 6.32434C5.20048 6.78937 4.46267 7.06848 3.65918 7.06848C1.77642 7.06848 0.250061 5.54204 0.25 3.6593C0.25 1.77651 1.77639 0.250122 3.65918 0.250122ZM3.65918 0.875122C2.12156 0.875122 0.875 2.12169 0.875 3.6593C0.875061 5.19687 2.1216 6.44348 3.65918 6.44348C5.19675 6.44347 6.4433 5.19686 6.44336 3.6593C6.44336 2.12169 5.19678 0.875135 3.65918 0.875122Z"
                        fill="currentColor" />
                  </svg>
                </div>
              ` : ''}
            </div>
          </td>
        </tr>
        
        <tr>
          <td class="mobile-table-head">
            <div>
              <span>мапа</span>
            </div>
          </td>
          <td class="mobile-table-info">
            ${board.x && board.y ? `
              <button class="show-on-map">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                  <g clip-path="url(#clip0_1_1882)">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M5.72092 1.01161C5.88421 0.918303 6.08334 0.912776 6.25156 0.996885L11.9823 3.86226L16.9709 1.01161C17.145 0.91213 17.3589 0.912845 17.5323 1.01349C17.7058 1.11413 17.8125 1.29949 17.8125 1.5V13.5C17.8125 13.7019 17.7043 13.8882 17.5291 13.9884L12.2791 16.9884C12.1158 17.0817 11.9167 17.0872 11.7484 17.0031L6.0177 14.1377L1.02908 16.9884C0.854983 17.0879 0.641093 17.0872 0.467666 16.9865C0.294239 16.8859 0.1875 16.7005 0.1875 16.5V4.5C0.1875 4.29814 0.295661 4.11176 0.470922 4.01161L5.72092 1.01161ZM6.0177 2.13775L1.3125 4.82643V15.5307L5.72092 13.0116C5.88421 12.9183 6.08334 12.9128 6.25156 12.9969L11.9823 15.8623L16.6875 13.1736V2.46929L12.2791 4.98839C12.1158 5.0817 11.9167 5.08722 11.7484 5.00312L6.0177 2.13775Z"
                          fill="#FC6B40" />
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M6 0.9375C6.31066 0.9375 6.5625 1.18934 6.5625 1.5V13.5C6.5625 13.8107 6.31066 14.0625 6 14.0625C5.68934 14.0625 5.4375 13.8107 5.4375 13.5V1.5C5.4375 1.18934 5.68934 0.9375 6 0.9375Z"
                          fill="#FC6B40" />
                    <path fill-rule="evenodd" clip-rule="evenodd"
                          d="M12 3.9375C12.3107 3.9375 12.5625 4.18934 12.5625 4.5V16.5C12.5625 16.8107 12.3107 17.0625 12 17.0625C11.6893 17.0625 11.4375 16.8107 11.4375 16.5V4.5C11.4375 4.18934 11.6893 3.9375 12 3.9375Z"
                          fill="#FC6B40" />
                  </g>
                  <defs>
                    <clipPath id="clip0_1_1882">
                      <rect width="18" height="18" fill="white" />
                    </clipPath>
                  </defs>
                </svg>
              </button>
            ` : ''}
          </td>
        </tr>
        
        <tr>
          <td class="mobile-table-head">
            <div>
              <span>зайнятість</span>
            </div>
          </td>
          <td class="mobile-table-info td-busy" data-basket="${board.basket}" data-busy="${board.reserve}">
          </td>
        </tr>
        
        <tr>
          <td class="mobile-table-head">
            <div>
              <span>вартість оренди, міс</span>
            </div>
          </td>
          <td class="mobile-table-info">
            <div class="price">
              ${renderPrice(board)}
            </div>
          </td>
        </tr>
        
        <tr>
          <td colspan="2" style="text-align: center;">
            <label class="select-construction-label">
              <input type="checkbox"
                    class="select-construction-checkbox"
                    name="id[]"
                    value="${board.id}"
                    ${isSelected ? 'checked' : ''}
                    hidden>
              <div class="select-button">
                <span class="to-choose">Обрати</span>
                <span class="choosen">Обрано</span>
              </div>
            </label>
          </td>
        </tr>
      </tbody>
    `;
    
    return table;
  }
  
  // Функция для рендеринга строки в десктопной таблице
  function renderBoardRow(board) {
    const isSelected = selectedIds.has(String(board.id));
    const selectedClass = isSelected ? 'selected' : '';

    return `
      <tr class="${selectedClass}">
        <td>
          <div class="td-code" data-code="${board.id}" data-select-month="">
            ${isOm ? board.code : `<a href="/${board.aleas}" target="_blank">${board.id}</a>`}
          </div>
        </td>
        <td>${isOm ? board.board_type : `<a href="/${board.aleas}" target="_blank">${board.board_type}</a>`}</td>
        <td class="desktop-table-address">
          ${isOm ? formatAddress(board) : `<a href="/${board.aleas}" target="_blank">${formatAddress(board)}</a>`}
        </td>
        <td>${isOm ? board.side_type : `<a href="/${board.aleas}" target="_blank">${board.side_type}</a>`}</td>
        <td>${renderLightingSvg(board.light)}</td>
        <td>${renderPhotoCell(board)}</td>
        <td>${board.x && board.y ? renderMapButton() : ''}</td>
        <td class="td-busy" data-basket="${board.basket}" data-busy="${board.reserve}"></td>
        <td class="desktop-table-price">
          <div class="price">
            ${renderPrice(board)}
          </div>
        </td>
        <td>
          <label class="select-construction-label">
            <input type="checkbox" class="select-construction-checkbox" name="id[]" value="${board.id}" hidden ${isSelected ? 'checked' : ''}>
            <div class="select-button">
              <span class="to-choose">Обрати</span>
              <span class="choosen">Обрано</span>
            </div>
          </label>
        </td>
      </tr>`;
  }
  
  // Вспомогательные функции для форматирования данных
  function formatAddress(board) {
    const showCity = !userRole || userRole != 1;
    let parts = [];
    if (showCity && board.city_name) parts.push(board.city_name);
    if (board.format) parts.push(board.format);
    if (board.addr) parts.push(board.addr);
    return parts.length ? parts.join(', ') : '-';
  }
  
  function renderLightingSvg(light) {
    const stroke = light == 1 ? '#4FB14B' : '#ADB0B9';
    return `<svg class="lighting" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
      <path d="M6.75 13.5H11.25" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" stroke="${stroke}"/>
      <path d="M7.5 16.5H10.5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" stroke="${stroke}"/>
      <path d="M11.3175 10.5C11.4525 9.765 11.805 9.195 12.375 8.625C12.7371 8.29163 13.0246 7.88537 13.2185 7.43294C13.4124 6.98051 13.5083 6.49216 13.5 6C13.5 4.80653 13.0259 3.66193 12.182 2.81802C11.3381 1.97411 10.1935 1.5 9 1.5C7.80653 1.5 6.66193 1.97411 5.81802 2.81802C4.97411 3.66193 4.5 4.80653 4.5 6C4.5 6.75 4.6725 7.6725 5.625 8.625C6.16804 9.12155 6.5385 9.77839 6.6825 10.5"  stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" stroke="${stroke}"/></svg>`;
  }
  
  function renderPhotoCell(board) {
    if (!board.image && !board.scheme) return '';
    const imgSrc = board.image ? `/upload/${board.image}` : `/upload/${board.scheme}`;
    return `<div class="construction-photo-wrapper">
      <img class="construction-photo construction-photo-desktop" src="${imgSrc}" alt="Фото конструкції">
      <div class="zoom-icon-wrapper">
        <svg class="zoom-icon img" xmlns="http://www.w3.org/2000/svg" width="8" height="8" data-alias="ua/${board.aleas}" data-id="${board.id}" data-img="${board.image ? `/upload/${board.image}|` : ''}${board.scheme ? `/upload/${board.scheme}` : ''}" viewBox="0 0 8 8" fill="none">
          <path d="M3.65918 0.250122C5.54196 0.250135 7.06836 1.77652 7.06836 3.6593C7.06833 4.51315 6.75311 5.29265 6.23438 5.89075L7.70312 7.54211L7.23633 7.95813L5.7832 6.32434C5.20048 6.78937 4.46267 7.06848 3.65918 7.06848C1.77642 7.06848 0.250061 5.54204 0.25 3.6593C0.25 1.77651 1.77639 0.250122 3.65918 0.250122ZM3.65918 0.875122C2.12156 0.875122 0.875 2.12169 0.875 3.6593C0.875061 5.19687 2.1216 6.44348 3.65918 6.44348C5.19675 6.44347 6.4433 5.19686 6.44336 3.6593C6.44336 2.12169 5.19678 0.875135 3.65918 0.875122Z" fill="currentColor" />
        </svg>
      </div>
    </div>`;
  }
  
  function renderMapButton() {
    return `<button class="show-on-map">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
        <g clip-path="url(#clip0_1_1882)">
          <path fill-rule="evenodd" clip-rule="evenodd"
              d="M5.72092 1.01161C5.88421 0.918303 6.08334 0.912776 6.25156 0.996885L11.9823 3.86226L16.9709 1.01161C17.145 0.91213 17.3589 0.912845 17.5323 1.01349C17.7058 1.11413 17.8125 1.29949 17.8125 1.5V13.5C17.8125 13.7019 17.7043 13.8882 17.5291 13.9884L12.2791 16.9884C12.1158 17.0817 11.9167 17.0872 11.7484 17.0031L6.0177 14.1377L1.02908 16.9884C0.854983 17.0879 0.641093 17.0872 0.467666 16.9865C0.294239 16.8859 0.1875 16.7005 0.1875 16.5V4.5C0.1875 4.29814 0.295661 4.11176 0.470922 4.01161L5.72092 1.01161ZM6.0177 2.13775L1.3125 4.82643V15.5307L5.72092 13.0116C5.88421 12.9183 6.08334 12.9128 6.25156 12.9969L11.9823 15.8623L16.6875 13.1736V2.46929L12.2791 4.98839C12.1158 5.0817 11.9167 5.08722 11.7484 5.00312L6.0177 2.13775Z"
              fill="#FC6B40" />
          <path fill-rule="evenodd" clip-rule="evenodd"
              d="M6 0.9375C6.31066 0.9375 6.5625 1.18934 6.5625 1.5V13.5C6.5625 13.8107 6.31066 14.0625 6 14.0625C5.68934 14.0625 5.4375 13.8107 5.4375 13.5V1.5C5.4375 1.18934 5.68934 0.9375 6 0.9375Z"
              fill="#FC6B40" />
          <path fill-rule="evenodd" clip-rule="evenodd"
              d="M12 3.9375C12.3107 3.9375 12.5625 4.18934 12.5625 4.5V16.5C12.5625 16.8107 12.3107 17.0625 12 17.0625C11.6893 17.0625 11.4375 16.8107 11.4375 16.5V4.5C11.4375 4.18934 11.6893 3.9375 12 3.9375Z"
              fill="#FC6B40" />
        </g>
        <defs>
          <clipPath id="clip0_1_1882">
            <rect width="18" height="18" fill="white" />
          </clipPath>
        </defs>
      </svg>
    </button>`;
  }
  
  function renderPrice(board) {
    if (userRole && userRole < 3) return `<a href="#" class="cost-board">${board.approximated_selling_price} ₴</a>`;
    if (isOm) return `<a href="#" class="cost-board">${board.approximated_selling_price} ₴</a>`;
    if (!board.approximated_selling_price) return `<a href="#" class="cost-board">@lang('message.hidden_price_word')</a>`;
    return `<a href="#" class="cost-board">${board.approximated_selling_price} ₴</a>`;
  }
  
  // Обновление информации о диапазоне элементов
  function updateRangeDisplay(start, count) {
    const end = start + count;
    rangeDisplay.textContent = `${start + 1}–${end} з ${boards.length}`;
  }
  
  // Обновление кнопок пагинации
  function updatePaginationControls() {
  const totalPages = Math.ceil(boards.length / rowsPerPage);
  pagesContainer.innerHTML = '';

  prevPageBtn.disabled = (currentPage <= 1);
  nextPageBtn.disabled = (currentPage >= totalPages);

  let startPage = Math.max(1, currentPage - 2);
  let endPage = Math.min(totalPages, startPage + 3);

  // Корректировка, если ближе к концу списка
  if (endPage - startPage < 3) {
    startPage = Math.max(1, endPage - 3);
  }

  for (let i = startPage; i <= endPage; i++) {
    const btn = document.createElement('button');
    btn.textContent = i;
    btn.classList.add('page-button');
    if (i === currentPage) btn.classList.add('active');
    btn.onclick = () => {
      currentPage = i;
      renderPage(currentPage);
    };
    pagesContainer.appendChild(btn);
  }
}


//   ч2
 // ------------------ ОБРАБОТЧИКИ СОБЫТИЙ ------------------
  
  // Добавление обработчиков событий к новым элементам
  function addEventListeners() {
    // Обработчики для чекбоксов
    document.querySelectorAll('.select-construction-checkbox').forEach(checkbox => {
      checkbox.addEventListener('change', function() {
        const id = this.value;
        const row = this.closest('tr');
        const mobileTable = this.closest('.constructions-table-mobile');
        
        if (this.checked) {
          selectedIds.add(id);
          if (row) row.classList.add('selected');
          if (mobileTable) mobileTable.classList.add('checked');
        } else {
          selectedIds.delete(id);
          if (row) row.classList.remove('selected');
          if (mobileTable) mobileTable.classList.remove('checked');
        }
        
        // Синхронизируем состояние чекбоксов для одного и того же элемента
        document.querySelectorAll(`.select-construction-checkbox[value="${id}"]`).forEach(cb => {
          if (cb !== this) {
            cb.checked = this.checked;
            
            const otherRow = cb.closest('tr');
            const otherTable = cb.closest('.constructions-table-mobile');
            
            if (otherRow) {
              if (this.checked) otherRow.classList.add('selected');
              else otherRow.classList.remove('selected');
            }
            
            if (otherTable) {
              if (this.checked) otherTable.classList.add('checked');
              else otherTable.classList.remove('checked');
            }
          }
        });
      });
    });
    document.querySelectorAll('.select-construction-mobile-checkbox').forEach(checkbox => {
  checkbox.addEventListener('change', (e) => {
    const id = e.target.value;
    if (e.target.checked) {
      selectedIds.add(id);
    } else {
      selectedIds.delete(id);
    }

    // Обновим таблицу — чтобы перекрасить строки
    renderPage(currentPage);
  });
});
    // Обработчики для фото
    document.querySelectorAll('.construction-photo-wrapper, .construction-photo-wrapper-mobile').forEach(wrapper => {
    wrapper.addEventListener('click', function(e) {
        e.stopPropagation();

        console.log('Клик по фото-обертке:', this.className);

        const modalBackdropDesktop = document.querySelector('.map-modal-backdrop');
        const modalBackdropMobile = document.querySelector('.map-modal-backdrop-mobile');

        console.log('Модальные окна:', {
            desktop: modalBackdropDesktop,
            mobile: modalBackdropMobile
        });

        const isMobile = this.classList.contains('construction-photo-wrapper-mobile') || window.innerWidth <= 768;
        console.log('isMobile:', isMobile);

        const row = this.closest('tr');
        const mobileTable = this.closest('.constructions-table-mobile');
        const imgSrc = this.querySelector('.construction-photo, .construction-photo-desktop')?.getAttribute('src');

        console.log('Данные элемента:', {
            row: row,
            mobileTable: mobileTable,
            imgSrc: imgSrc
        });

        let boardId;
        if (mobileTable) {
            // Для мобильной версии: ищем чекбокс внутри construction-photo-wrapper-mobile
            boardId = this.closest('.constructions-table-mobile').querySelector('.select-construction-checkbox')?.value;
        } else if (row) {
            // Для десктопной версии
            boardId = row.querySelector('.select-construction-checkbox')?.value;
        }

        console.log('boardId:', boardId);

        const board = boards.find(b => String(b.id) === String(boardId));

        console.log('board:', board);

        if (!board) {
            console.error('Не удалось найти данные для boardId:', boardId);
            return;
        }

        if (isMobile) {
            console.log('Открываем мобильное модальное окно');
            // Для мобильной версии
            const photoImage = document.getElementById('photoImage');
            const photoTab = document.getElementById('photoTab');

            console.log('Элементы мобильного модального окна:', {
                photoImage: photoImage,
                photoTab: photoTab
            });

            // Заголовок модального окна
            const modalTitle = modalBackdropMobile.querySelector('.header-wrapper h3');
            if (modalTitle) {
                modalTitle.innerHTML = `<span>${board.id}</span>, <span>${board.board_type}</span>`;
            }

            // Адрес
            const locationText = modalBackdropMobile.querySelector('.location-text');
            if (locationText) {
                locationText.textContent = formatAddress(board);
            }

            // Информация в таблице
            const infoTable = modalBackdropMobile.querySelector('.map-modal-mobile-info-table');
            if (infoTable) {
                const rows = infoTable.querySelectorAll('tbody tr');
                if (rows[0]) rows[0].querySelector('.td-info').textContent = board.id;
                if (rows[1]) rows[1].querySelector('.td-info').textContent = board.board_type;
                if (rows[2]) rows[2].querySelector('.td-info').textContent = board.side_type;
                if (rows[3]) {
                    const lightCell = rows[3].querySelector('.td-info');
                    if (lightCell) {
                        lightCell.innerHTML = renderLightingSvg(board.light);
                    }
                }
            }

            // Цена
            const priceElement = modalBackdropMobile.querySelector('.map-modal-mobile-price-wrapper .price');
            if (priceElement) {
                priceElement.textContent = board.approximated_selling_price ? `${board.approximated_selling_price} ₴` : '@lang("message.hidden_price_word")';
            }

            // Чекбокс
            const checkbox = modalBackdropMobile.querySelector('.select-construction-mobile-checkbox');
            if (checkbox) {
                checkbox.checked = selectedIds.has(String(board.id));
                checkbox.value = board.id;
            }

            // Изображения
            if (photoImage && imgSrc) {
                photoImage.src = imgSrc;
            }

            // Переключаем на вкладку с фото
            if (photoTab) {
                photoTab.checked = true;
            }

            // Показываем модальное окно
            modalBackdropMobile.style.display = 'flex';
            document.body.classList.add('modal-open');
        } else {
          // Для десктопной версии
          const modalPriceDesktop = modalBackdropDesktop.querySelector('.price');
          const modalAddressDesktop = modalBackdropDesktop.querySelector('.location-text');
          const modalImageDesktop = modalBackdropDesktop.querySelector('.modal-photo');
          const modalCheckboxDesktop = modalBackdropDesktop.querySelector('.photo-modal-desktop-checkbox');
          
          // Заполняем данные
          if (modalPriceDesktop) {
            modalPriceDesktop.textContent = board.approximated_selling_price ? `${board.approximated_selling_price} ₴` : '@lang("message.hidden_price_word")';
          }
          
          if (modalAddressDesktop) {
            modalAddressDesktop.textContent = formatAddress(board);
          }
          
          if (modalImageDesktop && imgSrc) {
            modalImageDesktop.setAttribute('src', imgSrc);
          }
          
          if (modalCheckboxDesktop) {
            modalCheckboxDesktop.checked = selectedIds.has(String(board.id));
            modalCheckboxDesktop.value = board.id;
            
            // Добавляем обработчик для синхронизации состояния
            modalCheckboxDesktop.onchange = function() {
              const isChecked = this.checked;
              
              // Обновляем Set с выбранными ID
              if (isChecked) {
                selectedIds.add(String(board.id));
              } else {
                selectedIds.delete(String(board.id));
              }
              
              // Обновляем все чекбоксы с этим ID
              document.querySelectorAll(`.select-construction-checkbox[value="${board.id}"]`).forEach(cb => {
                cb.checked = isChecked;
                
                const cbRow = cb.closest('tr');
                const cbTable = cb.closest('.constructions-table-mobile');
                
                if (cbRow) {
                  if (isChecked) cbRow.classList.add('selected');
                  else cbRow.classList.remove('selected');
                }
                
                if (cbTable) {
                  if (isChecked) cbTable.classList.add('checked');
                  else cbTable.classList.remove('checked');
                }
              });
            };
          }
          
          // Показываем модальное окно
          modalBackdropDesktop.style.display = 'flex';
          document.body.classList.add('modal-open');
        }
      });
    });

    document.querySelectorAll('.construction-photo-wrapper-mobile').forEach(wrapper => {
  wrapper.addEventListener('click', function(e) {
    console.log('Прямой клик по construction-photo-wrapper-mobile');
    // Убедимся, что клик не обрабатывается дважды
    e.stopPropagation();
    
    const modalBackdropMobile = document.querySelector('.map-modal-backdrop-mobile');
    if (modalBackdropMobile) {
      modalBackdropMobile.style.display = 'flex';
      document.body.classList.add('modal-open');
    } else {
      console.error('Не найден элемент .map-modal-backdrop-mobile');
    }
  });
});
   //  ч3
   
    // Обработчики для кнопок карты
    
  }
  
  // Закрытие модальных окон
  document.querySelectorAll('.close-map-modal-btn--cross').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelector('.map-modal-backdrop').style.display = 'none';
      document.body.classList.remove('modal-open');
    });
  });
  
  document.querySelectorAll('.close-map-modal-btn--cross-mobile').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelector('.map-modal-backdrop-mobile').style.display = 'none';
      document.body.classList.remove('modal-open');
    });
  });
  
  document.querySelector('.map-modal-backdrop')?.addEventListener('click', function(e) {
    if (e.target === this) {
      this.style.display = 'none';
      document.body.classList.remove('modal-open');
    }
  });
  
  document.querySelector('.map-modal-backdrop-mobile')?.addEventListener('click', function(e) {
    if (e.target === this) {
      this.style.display = 'none';
      document.body.classList.remove('modal-open');
    }
  });
  
  // Переключение вкладок в мобильном модальном окне
  const mapRadio = document.getElementById('mapTab');
  const photoRadio = document.getElementById('photoTab');
  const mapImage = document.getElementById('mapImage');
  const photoImage = document.getElementById('photoImage');
  
  function toggleImages() {
    if (mapRadio?.checked) {
      mapImage.style.display = 'block';
      photoImage.style.display = 'none';
    } else {
      mapImage.style.display = 'none';
      photoImage.style.display = 'block';
    }
  }
  
  mapRadio?.addEventListener('change', toggleImages);
  photoRadio?.addEventListener('change', toggleImages);
  
  // Инициализация страницы
  renderPage(currentPage);
});



</script>
<body>
  @include('add.header')
  @include('add.menu')
  @include('add.bread')  
  
  <div class="container container-base">
  <section class="cd-section">
         <div class="cd-wrapper">
            <div class="cd-client-data-wrapper">
               <h2 class="cd-proposal-to-title">Пропозиція для:</h2>

               <h3 class="cd-clien-name">ТИЩЕНКО ГЕННАДІЙ</h3>

               <div class="cd-client-info-wrapper">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                     <path
                        d="M16.64 13.3158V15.323C16.6408 15.5094 16.6026 15.6938 16.5279 15.8645C16.4533 16.0353 16.3438 16.1885 16.2065 16.3145C16.0692 16.4405 15.9071 16.5364 15.7306 16.5961C15.5541 16.6558 15.367 16.6779 15.1814 16.6612C13.1226 16.4374 11.1449 15.7339 9.40737 14.6071C7.79079 13.5799 6.42021 12.2093 5.39297 10.5927C4.26223 8.84724 3.55854 6.85997 3.33893 4.79189C3.32221 4.60687 3.3442 4.42039 3.40349 4.24434C3.46279 4.06828 3.55809 3.9065 3.68334 3.7693C3.80859 3.63209 3.96103 3.52247 4.13096 3.44741C4.3009 3.37235 4.4846 3.33349 4.67037 3.33332H6.67758C7.00228 3.33012 7.31707 3.4451 7.56326 3.65683C7.80946 3.86856 7.97027 4.16259 8.01571 4.48411C8.10043 5.12646 8.25755 5.75717 8.48406 6.3642C8.57408 6.60367 8.59356 6.86393 8.5402 7.11414C8.48684 7.36435 8.36287 7.59402 8.18298 7.77593L7.33326 8.62565C8.28572 10.3007 9.67263 11.6876 11.3477 12.6401L12.1974 11.7903C12.3793 11.6105 12.609 11.4865 12.8592 11.4331C13.1094 11.3798 13.3696 11.3992 13.6091 11.4893C14.2161 11.7158 14.8469 11.8729 15.4892 11.9576C15.8142 12.0035 16.111 12.1672 16.3232 12.4176C16.5354 12.668 16.6481 12.9877 16.64 13.3158Z"
                        stroke="#8B8F9D" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>

                  <a href="tel:+380999291924" class="cd-client-info cd-client-phone">+380 99 929 1924</a>
               </div>

               <div class="cd-client-info-wrapper">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                     <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M1.0415 3.54175H18.9582V16.4584H1.0415V3.54175ZM2.2915 4.79175V15.2084H17.7082V4.79175H2.2915Z"
                        fill="#8B8F9D" />
                     <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M2.10525 5.87622L10.0005 8.50798L17.8958 5.87622L18.2911 7.06208L10.0005 9.8256L1.70996 7.06208L2.10525 5.87622Z"
                        fill="#8B8F9D" />
                  </svg>

                  <a href="mailto:tyshenko.gennadiy@gmail.com"
                     class=" cd-client-info cd-clien-email">tyshenko.gennadiy@gmail.com</a>
               </div>

            </div>

            <div class="cd-divider-wrapper">
               <div class="cd-divider"></div>
            </div>

            <div class="cd-manager-data-wrapper">
               <h3 class="cd-manager-title">Деталі</h3>

               <div class="cd-manager-inner-wrapper">

                  <div class="cd-manager-titles">
                     <h4 class="cd-manager-inner-title">Дата створення</h4>
                     <h4 class="cd-manager-inner-title">Менеджер</h4>
                     <h4 class="cd-manager-inner-title">Контакти</h4>
                  </div>

                  <div class="cd-manager-data">
                     <p class="cd-manager-inner-text">14.08.2025</p>
                     <p class="cd-manager-inner-text">Арсеній</p>
                     <p class="cd-manager-inner-text cd-manager-phone-wrapper">
                        <svg xmlns="http://www.w3.org/2000/svg" width="21" height="20" viewBox="0 0 21 20" fill="none">
                           <path
                              d="M17.14 13.3158V15.323C17.1408 15.5094 17.1026 15.6938 17.0279 15.8645C16.9533 16.0353 16.8438 16.1885 16.7065 16.3145C16.5692 16.4405 16.4071 16.5364 16.2306 16.5961C16.0541 16.6558 15.867 16.6779 15.6814 16.6612C13.6226 16.4374 11.6449 15.7339 9.90737 14.6071C8.29079 13.5799 6.92021 12.2093 5.89297 10.5927C4.76223 8.84724 4.05854 6.85997 3.83893 4.79189C3.82221 4.60687 3.8442 4.42039 3.90349 4.24434C3.96279 4.06828 4.05809 3.9065 4.18334 3.7693C4.30859 3.63209 4.46103 3.52247 4.63096 3.44741C4.8009 3.37235 4.9846 3.33349 5.17037 3.33332H7.17758C7.50228 3.33012 7.81707 3.4451 8.06326 3.65683C8.30946 3.86856 8.47027 4.16259 8.51571 4.48411C8.60043 5.12646 8.75755 5.75717 8.98406 6.3642C9.07408 6.60367 9.09356 6.86393 9.0402 7.11414C8.98684 7.36435 8.86287 7.59402 8.68298 7.77593L7.83326 8.62565C8.78572 10.3007 10.1726 11.6876 11.8477 12.6401L12.6974 11.7903C12.8793 11.6105 13.109 11.4865 13.3592 11.4331C13.6094 11.3798 13.8696 11.3992 14.1091 11.4893C14.7161 11.7158 15.3469 11.8729 15.9892 11.9576C16.3142 12.0035 16.611 12.1672 16.8232 12.4176C17.0354 12.668 17.1481 12.9877 17.14 13.3158Z"
                              stroke="#FC6B40" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span class="cd-manager-inner-phone">+380 99 929 1924</span>
                     </p>


                     <div class="socials-container">
                        <a href="#"><svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24"
                              fill="none">
                              <circle cx="12.5" cy="12" r="12" fill="#3D445C" />
                              <path
                                 d="M9.65181 15.8964L10.0862 16.1502C10.8187 16.5775 11.6519 16.8018 12.5 16.8C13.4494 16.8 14.3774 16.5185 15.1667 15.991C15.9561 15.4636 16.5713 14.714 16.9346 13.8369C17.2979 12.9598 17.393 11.9947 17.2078 11.0636C17.0226 10.1325 16.5654 9.27718 15.8941 8.60589C15.2228 7.93459 14.3675 7.47744 13.4364 7.29223C12.5053 7.10702 11.5402 7.20208 10.6631 7.56538C9.78604 7.92868 9.03638 8.54391 8.50895 9.33326C7.98152 10.1226 7.70001 11.0506 7.70001 12C7.69826 12.8483 7.92277 13.6817 8.35041 14.4144L8.60361 14.8488L8.21181 16.2894L9.65181 15.8964ZM6.50241 18L7.31361 15.0192C6.77898 14.103 6.49815 13.0608 6.50001 12C6.50001 8.6862 9.18621 6 12.5 6C15.8138 6 18.5 8.6862 18.5 12C18.5 15.3138 15.8138 18 12.5 18C11.4397 18.0018 10.398 17.7212 9.48201 17.187L6.50241 18ZM10.3346 9.1848C10.4154 9.1792 10.496 9.1784 10.5764 9.1824C10.6088 9.1848 10.6412 9.188 10.6736 9.192C10.769 9.2028 10.874 9.261 10.9094 9.3414C11.0882 9.7474 11.2618 10.1554 11.4302 10.5654C11.4674 10.6566 11.4452 10.7736 11.3744 10.8876C11.3384 10.9458 11.282 11.0274 11.2166 11.1108C11.1488 11.1978 11.003 11.3574 11.003 11.3574C11.003 11.3574 10.9436 11.4282 10.9664 11.5164C10.9748 11.55 11.0024 11.5986 11.0276 11.6394L11.063 11.6964C11.2166 11.9526 11.423 12.2124 11.675 12.4572C11.747 12.5268 11.8172 12.5982 11.8928 12.6648C12.1736 12.9126 12.4916 13.1148 12.8348 13.2648L12.8378 13.266C12.8888 13.2882 12.9146 13.3002 12.989 13.332C13.0262 13.3476 13.0644 13.3608 13.1036 13.3716C13.118 13.3756 13.1326 13.3778 13.1474 13.3782C13.1816 13.3795 13.2156 13.3724 13.2465 13.3576C13.2773 13.3427 13.3041 13.3205 13.3244 13.293C13.7582 12.7674 13.7984 12.7332 13.8014 12.7332V12.7344C13.8315 12.7062 13.8674 12.6848 13.9065 12.6716C13.9456 12.6585 13.9871 12.6539 14.0282 12.6582C14.065 12.6598 14.1004 12.6678 14.1344 12.6822C14.453 12.828 14.9744 13.0554 14.9744 13.0554L15.3236 13.212C15.3824 13.2402 15.4358 13.3068 15.4376 13.371C15.44 13.4112 15.4436 13.476 15.4298 13.5948C15.4106 13.7502 15.3638 13.9368 15.317 14.0346C15.2846 14.1011 15.2421 14.1622 15.191 14.2158C15.1304 14.2793 15.0641 14.3371 14.993 14.3886C14.9434 14.4254 14.9184 14.4434 14.918 14.4426C14.8434 14.49 14.7667 14.534 14.6882 14.5746C14.5337 14.6565 14.3631 14.7036 14.1884 14.7126C14.0774 14.7186 13.9664 14.727 13.8548 14.721C13.85 14.721 13.514 14.6688 13.514 14.6688C12.661 14.4444 11.8721 14.024 11.21 13.4412C11.0744 13.3218 10.9484 13.1934 10.82 13.0656C10.2872 12.5346 9.88341 11.9616 9.63801 11.4204C9.51351 11.1547 9.44606 10.8659 9.44001 10.5726C9.43775 10.2082 9.55704 9.85354 9.77901 9.5646C9.82281 9.5082 9.86421 9.4494 9.93561 9.3816C10.0112 9.3096 10.0598 9.2712 10.112 9.2448C10.1816 9.2106 10.2572 9.19022 10.3346 9.1848Z"
                                 fill="white" />
                           </svg></a>


                        <a href="#"><svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24"
                              fill="none">
                              <circle cx="12.5" cy="12" r="12" fill="white" />
                              <path fill-rule="evenodd" clip-rule="evenodd"
                                 d="M12.5 24C5.87258 24 0.5 18.6274 0.5 12C0.5 5.37258 5.87258 0 12.5 0C19.1274 0 24.5 5.37258 24.5 12C24.5 18.6274 19.1274 24 12.5 24ZM13.0189 9.00553C11.9441 9.45256 9.79616 10.3778 6.57494 11.7812C6.05186 11.9892 5.77785 12.1927 5.75291 12.3917C5.71075 12.728 6.13186 12.8604 6.7053 13.0407C6.78331 13.0652 6.86413 13.0906 6.94699 13.1176C7.51117 13.301 8.27009 13.5155 8.66462 13.524C9.0225 13.5318 9.42193 13.3842 9.86292 13.0814C12.8726 11.0498 14.4262 10.0229 14.5238 10.0008C14.5926 9.98516 14.6879 9.96553 14.7525 10.023C14.8171 10.0804 14.8108 10.1891 14.8039 10.2183C14.7622 10.3962 13.1092 11.9329 12.2538 12.7282C11.9871 12.9762 11.7979 13.152 11.7593 13.1922C11.6726 13.2822 11.5844 13.3673 11.4995 13.4491C10.9754 13.9543 10.5823 14.3332 11.5213 14.952C11.9725 15.2494 12.3336 15.4952 12.6938 15.7405C13.0872 16.0084 13.4795 16.2757 13.9872 16.6084C14.1165 16.6932 14.2401 16.7813 14.3604 16.8671C14.8182 17.1934 15.2295 17.4867 15.7376 17.4399C16.0329 17.4127 16.3379 17.1351 16.4928 16.307C16.8588 14.3501 17.5784 10.1101 17.7447 8.36285C17.7592 8.20977 17.7409 8.01386 17.7262 7.92786C17.7115 7.84186 17.6807 7.71932 17.5689 7.62862C17.4365 7.52119 17.2322 7.49854 17.1407 7.50007C16.7251 7.50747 16.0875 7.72919 13.0189 9.00553Z"
                                 fill="#3D445C" />
                           </svg></a>


                        <a href="#"><svg width="25" height="24" viewBox="0 0 25 24" fill="none"
                              xmlns="http://www.w3.org/2000/svg">
                              <g clip-path="url(#clip0_1_2006)">
                                 <circle cx="12.5" cy="12" r="12" fill="#3D445C" />
                                 <path
                                    d="M13.0898 6.02246C13.1407 6.02378 13.1802 6.02523 13.207 6.02637C13.2204 6.02694 13.2314 6.027 13.2383 6.02734C13.2415 6.02751 13.2444 6.02823 13.2461 6.02832H13.249C15.9283 6.03957 17.3135 6.7657 17.8438 7.14453L18.0205 7.28223C19.1439 8.20561 19.7713 10.2601 19.3496 13.5195C19.1459 15.0958 18.418 15.9583 17.6875 16.4443C16.9557 16.9312 16.2211 17.0402 15.9961 17.1094C15.8051 17.1682 14.1162 17.5689 11.9199 17.3936H11.9111L11.9043 17.4004C11.6617 17.6679 11.3403 18.0179 11.1074 18.2559C11.0011 18.3646 10.9043 18.472 10.8135 18.5684C10.7224 18.665 10.6367 18.751 10.5508 18.8193C10.3796 18.9556 10.2089 19.0203 9.99414 18.957C9.8195 18.9057 9.73549 18.7539 9.69531 18.6104C9.67532 18.5389 9.66685 18.4705 9.66309 18.4199C9.66121 18.3947 9.66031 18.3737 9.66016 18.3594V18.3369L9.66211 17.0273V17.0088H9.64258C8.04331 16.5812 7.2558 15.5813 6.87207 14.4775C6.48767 13.3718 6.50816 12.1619 6.52637 11.3174C6.56274 9.6307 6.90711 8.2742 7.88281 7.34863C8.75973 6.58612 10.1004 6.25606 11.2236 6.11621C11.7848 6.04636 12.2913 6.0241 12.6572 6.01953C12.84 6.01725 12.9878 6.01981 13.0898 6.02246ZM12.6895 6.89844C12.3778 6.90051 11.9466 6.91646 11.4688 6.97266C10.5139 7.08498 9.36808 7.35785 8.61816 8.00195L8.61719 8.00293C7.78205 8.78515 7.50259 9.9469 7.47168 11.3584C7.45627 12.0626 7.40837 13.0925 7.70605 14.0352C8.00326 14.976 8.64529 15.8302 10.0029 16.1924C10.0029 16.195 10.0029 16.1979 10.0029 16.2012C10.0029 16.2158 10.0021 16.2373 10.002 16.2646C10.0017 16.3197 10.0015 16.399 10.001 16.4941C9.99996 16.6843 9.99848 16.9395 9.99707 17.2002C9.99424 17.7221 9.99183 18.2667 9.99121 18.3545C9.99082 18.4164 9.99514 18.4709 10.0107 18.5127C10.0267 18.5554 10.0549 18.5861 10.0996 18.5967C10.1614 18.6116 10.2479 18.5809 10.3184 18.5137C10.5365 18.3058 10.9936 17.804 11.3955 17.3545C11.5966 17.1296 11.7846 16.9175 11.9219 16.7617C11.9905 16.6838 12.047 16.6196 12.0859 16.5752C12.1054 16.5531 12.1206 16.5352 12.1309 16.5234C12.1346 16.5192 12.1373 16.5154 12.1396 16.5127C14.0055 16.6269 15.4886 16.2771 15.6514 16.2275C15.8354 16.1715 16.4453 16.1008 17.0479 15.7129C17.6522 15.3239 18.2506 14.6163 18.4238 13.29V13.2891C18.6022 11.9259 18.5695 10.778 18.3721 9.87305C18.1746 8.96814 17.812 8.3042 17.3291 7.91211H17.3281C17.0337 7.65945 15.7861 6.91893 13.1924 6.9082H13.1846C13.1788 6.90788 13.1703 6.90678 13.1592 6.90625C13.1363 6.90516 13.1021 6.90371 13.0586 6.90234C12.9715 6.89961 12.8453 6.8974 12.6895 6.89844Z"
                                    fill="white" stroke="white" stroke-width="0.0374071" />
                                 <path d="M14.3004 10.9399C14.2792 10.5439 14.0626 10.3359 13.6504 10.3159"
                                    stroke="white" stroke-width="0.630683" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                 <path
                                    d="M15.5992 11.5639C15.6155 10.8764 15.3926 10.3021 14.9307 9.8408C14.4666 9.37789 13.8243 9.123 13 9.06787"
                                    stroke="white" stroke-width="0.630683" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                 <path
                                    d="M16.25 11.5641C16.242 10.5995 15.9285 9.83986 15.3095 9.28509C14.6905 8.73032 13.9207 8.44999 13 8.44409"
                                    stroke="white" stroke-width="0.630683" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                 <path
                                    d="M9.11914 9.66968C9.11774 9.645 9.11862 9.61981 9.12305 9.59546L9.12402 9.59448C9.1463 9.42327 9.23405 9.24657 9.39062 9.06519L9.39453 9.06128C9.58801 8.85367 9.81418 8.67635 10.0654 8.53687V8.53784L10.0742 8.53394C10.1992 8.47146 10.32 8.45102 10.4287 8.46851C10.5374 8.48598 10.6359 8.54115 10.7158 8.63159L10.7168 8.63354C10.7175 8.6343 10.7186 8.63519 10.7197 8.63647C10.7223 8.63939 10.7258 8.64376 10.7305 8.64917L10.9062 8.85425L11.2051 9.21948C11.3559 9.42318 11.4961 9.63418 11.625 9.85132C11.7884 10.133 11.6851 10.4191 11.5273 10.5359L11.1934 10.7908C11.1042 10.8599 11.0658 10.9585 11.0498 11.0378C11.0418 11.0774 11.0396 11.1129 11.0391 11.1384V11.1775L11.04 11.1794L11.0391 11.1804L11.04 11.1843L11.041 11.1853C11.0413 11.1862 11.0415 11.1876 11.042 11.1892C11.043 11.1927 11.0448 11.1981 11.0469 11.2048C11.051 11.2183 11.0568 11.2383 11.0654 11.2634C11.0827 11.3138 11.1097 11.3863 11.1475 11.4744C11.2231 11.6507 11.3434 11.8903 11.5215 12.1443C11.8778 12.6525 12.4663 13.2183 13.3955 13.446L13.3984 13.447H13.4424C13.4689 13.4464 13.5057 13.444 13.5469 13.4363C13.629 13.4209 13.7308 13.3845 13.8027 13.2996H13.8037L14.0684 12.9792C14.1904 12.8277 14.4894 12.7279 14.7842 12.8855C15.0103 13.0093 15.2303 13.143 15.4424 13.2878C15.5423 13.3584 15.6944 13.476 15.8223 13.5759C15.8862 13.6259 15.9445 13.6718 15.9863 13.7048C16.0071 13.7213 16.0244 13.7347 16.0361 13.7439C16.0417 13.7483 16.0457 13.7522 16.0488 13.7546C16.0503 13.7558 16.0518 13.7569 16.0527 13.7576L16.0537 13.7585C16.1486 13.8354 16.2064 13.9292 16.2246 14.033C16.2428 14.1367 16.2221 14.2521 16.1572 14.3718L16.1738 14.3806L16.1543 14.3708V14.3796C16.0092 14.6204 15.8252 14.8375 15.6094 15.0232C15.6071 15.0246 15.6055 15.0261 15.6045 15.0271L15.6064 15.0261L15.6035 15.0281C15.4153 15.179 15.2317 15.2635 15.0527 15.2839H15.0518C15.0261 15.2882 14.9997 15.29 14.9736 15.2888H14.9727C14.8952 15.2895 14.8179 15.2782 14.7441 15.2556L14.7432 15.2537L14.7412 15.2546H14.7402L14.748 15.2498L14.7334 15.2458L14.4854 15.1658C14.2935 15.0956 14.046 14.9901 13.7275 14.8347L13.1953 14.5632L12.8594 14.3777C12.5271 14.1859 12.2078 13.9738 11.9033 13.7429C11.7 13.5889 11.5058 13.4241 11.3223 13.2488L11.3027 13.2302L11.2832 13.2107L11.2637 13.1921H11.2627C11.2564 13.1863 11.2509 13.1801 11.2441 13.1736C11.0615 12.9973 10.889 12.8111 10.7285 12.616C10.4081 12.2264 10.1226 11.8115 9.875 11.3757C9.44153 10.6213 9.24228 10.1699 9.16309 9.90015L9.14551 9.90503L9.16895 9.89819L9.15527 9.8894L9.15234 9.88745C9.12933 9.81734 9.11834 9.74416 9.11914 9.67065V9.66968Z"
                                    fill="white" stroke="white" stroke-width="0.0374071" />
                              </g>
                              <defs>
                                 <clipPath id="clip0_1_2006">
                                    <rect width="24" height="24" fill="white" transform="translate(0.5)" />
                                 </clipPath>
                              </defs>
                           </svg></a>

                     </div>
                  </div>

               </div>
            </div>
         </div>
      </section>
   </div>

  <div id="manager-map"></div>

  <style>
     .constructions-table .tbody .td-busy .busy-calendar,
     .constructions-table-mobile .tbody .td-busy .busy-calendar {
        position: absolute;
        bottom: 80%;
        display: none;
        width: 297px;
        padding: 20px 25px 24px 25px;
        border-radius: 3px;
        background: white;
        box-shadow: 0 4px 20px 1px rgba(177, 177, 177, .5);
        cursor: auto;
     }
     .constructions-table .wrapp-busy,
     .constructions-table-mobile .wrapp-busy,
     .constructions-table .mob-view .busy-line,
     .constructions-table-mobile .mob-view .busy-line {
        display: flex;
        flex-direction: row;
     }
     .constructions-table .wrapp-busy .block,
     .constructions-table-mobile .wrapp-busy .block,
     .constructions-table .mob-view .busy-line .block,
     .constructions-table-mobile .mob-view .busy-line .block {
        width: 100%;
        height: 4px;
     }
     .constructions-table .wrapp-busy .busy,
     .constructions-table-mobile .wrapp-busy .busy,
     .constructions-table .mob-view .busy-line .wrapp-busy .busy,
     .constructions-table-mobile .mob-view .busy-line .wrapp-busy .busy {
        background: #e84a56;
     }
     .constructions-table .wrapp-busy .free,
     .constructions-table-mobile .wrapp-busy .free,
     .constructions-table .mob-view .busy-line .wrapp-busy .free,
     .constructions-table-mobile .mob-view .busy-line .wrapp-busy .free {
        background: #5cbc59;
     }
     .constructions-table .wrapp-busy .pre-order,
     .constructions-table-mobile .wrapp-busy .pre-order,
     .constructions-table .mob-view .busy-line .wrapp-busy .pre-order,
     .constructions-table-mobile .mob-view .busy-line .wrapp-busy .pre-order {
        background-color: #e6ecf2;
     }
     .constructions-table .wrapp-busy .reserve,
     .constructions-table-mobile .wrapp-busy .reserve,
     .constructions-table .mob-view .busy-line .wrapp-busy .reserve,
     .constructions-table-mobile .mob-view .busy-line .wrapp-busy .reserve{
        background-color: #FED648;
     }

     .constructions-table .td-busy,
     .constructions-table-mobile .td-busy{
        position: relative;
        overflow: unset;
        text-overflow: unset;
     }
     .constructions-table .td-busy .busy-calendar,
     .constructions-table-mobile .td-busy .busy-calendar {
        position: absolute;
        bottom: 80%;
        display: none;
        width: 297px;
        padding: 20px 25px 24px 25px;
        border-radius: 3px;
        background: white;
        box-shadow: 0 4px 20px 1px rgba(177, 177, 177, .5);
        cursor: auto;
     }
     .constructions-table .td-busy .busy-calendar:before,
     .constructions-table-mobile .td-busy .busy-calendar:before {
        position: absolute;
        bottom: -13px;
        left: 19px;
        border: 13px solid transparent;
        border-left: 10px solid white;
        content: '';
     }
     .constructions-table .td-busy .busy-calendar .title-calendar,
     .constructions-table-mobile .td-busy .busy-calendar .title-calendar
     {
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: bold;
     }
     .constructions-table .td-busy:hover .busy-calendar,
     .constructions-table-mobile .td-busy:hover .busy-calendar {
        z-index: 2;
        display: block;
        height: max-content;
     }
     .constructions-table .wrapp-busy-table,
     .constructions-table-mobile .wrapp-busy-table{
        display: flex;
        flex-wrap: wrap;
        width: 100%;
     }
     .constructions-table .wrapp-busy-table .month,
     .constructions-table-mobile .wrapp-busy-table .month{
        position: relative;
        cursor: pointer;
        width: 16.6%;
        box-sizing: border-box;
        border: 1px solid transparent;
        font-size: 14px;
     }
     .constructions-table .wrapp-busy-table .month a,
     .constructions-table-mobile .wrapp-busy-table .month a{
        display: block;
        padding: 6px 0;
        text-align: center;
        outline: none;
     }
     .constructions-table .wrapp-busy-table .month,
     .constructions-table-mobile .wrapp-busy-table .month{
        outline: none;
     }
     .constructions-table .wrapp-busy-table .month:nth-child(1),
     .constructions-table-mobile .wrapp-busy-table .month:nth-child(1){
        border-top-left-radius: 3px;
     }
     .constructions-table  .wrapp-busy-table .month.busy,
     .constructions-table-mobile  .wrapp-busy-table .month.busy{
        border-color: #e84a56;
        background: #e84a56;
        color: white;
     }
     .constructions-table .wrapp-busy-table .month.free,
     .constructions-table-mobile .wrapp-busy-table .month.free{
        border-color: #55bc4f;
        background: #55bc4f;
        color: white;
     }
     .constructions-table .wrapp-busy-table .month.reserve,
     .constructions-table-mobile .wrapp-busy-table .month.reserve{
        background-color: #FED648;
        color: black;
     }
     .constructions-table .wrapp-busy-table .month.pre-order,
     .constructions-table-mobile .wrapp-busy-table .month.pre-order{
        background-color: #e6ecf2;
        color: black;
     }
     .constructions-table .td-busy .busy-calendar .month.reserve,
     .constructions-table-mobile .td-busy .busy-calendar .month.reserve{
        background-color: #FED648;
     }
     .constructions-table .wrapp-busy-table .month.select:before,
     .constructions-table-mobile .wrapp-busy-table .month.select:before {
        position: absolute;
        left: calc(50% - 7.5px);
        top: calc(50% - 7.5px);
        width: 15px;
        height: 15px;
        border-radius: 50%;
        background-image: url(/assets/img/icons/check_mark.svg);
        background-position: center;
        background-repeat: no-repeat;
        background-size: 11px;
        z-index: 1;
        content: '';
        transition: all 0.4s;
     }
     .constructions-table .wrapp-busy-table .month.select:after,
     .constructions-table-mobile .wrapp-busy-table .month.select:after{
        position: absolute;
        left: calc(50% - 7.5px);
        top: calc(50% - 7.5px);
        width: 15px;
        height: 15px;
        border-radius: 50%;
        background: white;
        content: '';
        transition: all 0.4s;
     }
  </style>
  
  <div class="container container-base">
      <section class="proposal-constructions-section">
         <h2 class="proposal-title">Запропоновані рекламні конструкції:<sup>({{$boards->count()}})</sup></h2>
         {{--
         <p class="proposal-constructions-section--text">Період розміщення: з <span>01.06.2025</span> - по
            <span>31.08.2025</span>
         </p>
         --}}
         <p class="proposal-constructions-section--text">@lang('message.actual_employment') на <span class="date"> {{ date('d.m.Y') }}</span>.
         </p>

         <div class="table-tools">
            <div class="table-tools__export">
               <a class="table-tools__export-btn btn--xls" href="#">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24">
                     <path d="M12 16V4M12 16l-5-5m5 5l5-5M5 20h14" stroke="#f15a29" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  Завантажити в .xls </a>
               {{--
               <a class="table-tools__export-btn btn--pdf" href="#">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                     <path
                        d="M17.292 5.99121V16.667C17.2919 17.2747 17.0498 17.8574 16.6201 18.2871C16.1904 18.7168 15.6077 18.958 15 18.958H5C4.39229 18.958 3.80964 18.7168 3.37988 18.2871C2.95019 17.8574 2.70809 17.2747 2.70801 16.667V3.33301C2.70809 2.72534 2.95019 2.14258 3.37988 1.71289C3.80964 1.28322 4.39229 1.04199 5 1.04199H12.3418L17.292 5.99121ZM5 2.29199C4.72381 2.29199 4.45901 2.40143 4.26367 2.59668C4.0684 2.79195 3.95809 3.05686 3.95801 3.33301V16.667C3.95809 16.9431 4.0684 17.208 4.26367 17.4033C4.45901 17.5986 4.72381 17.708 5 17.708H15C15.2762 17.708 15.541 17.5986 15.7363 17.4033C15.9316 17.208 16.0419 16.9431 16.042 16.667V7.29199H11.042V2.29199H5ZM13.333 14.792H6.66699V13.542H13.333V14.792ZM13.333 11.458H6.66699V10.208H13.333V11.458ZM8.33301 8.125H6.66699V6.875H8.33301V8.125ZM12.292 6.04199H15.5752L12.292 2.75879V6.04199Z"
                        fill="#FC6B40" />
                  </svg>
                  Завантажити в .pdf</a>
               <a class="table-tools__export-btn btn--ppt" href="#">
                  <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                     <path
                        d="M16.5381 1.67773C17.5464 1.78031 18.334 2.63157 18.334 3.66699V11.334L18.3232 11.5381C18.2276 12.4792 17.4792 13.2276 16.5381 13.3232L16.334 13.334H10.75V15.917H14.166V17.417H5.83301V15.917H9.25V13.334H3.66699L3.46289 13.3232C2.52155 13.2278 1.77348 12.4793 1.67773 11.5381L1.66699 11.334V3.66699C1.66699 2.63134 2.45422 1.78 3.46289 1.67773L3.66699 1.66699H16.334L16.5381 1.67773ZM3.66699 3.16699C3.39085 3.16699 3.16699 3.39085 3.16699 3.66699V11.334C3.16734 11.6098 3.39107 11.834 3.66699 11.834H16.334C16.6096 11.8336 16.8336 11.6096 16.834 11.334V3.66699C16.834 3.39107 16.6098 3.16734 16.334 3.16699H3.66699Z"
                        fill="#FC6B40" />
                     <path d="M9.75 7.93262V7.06738L10.5 7.5L9.75 7.93262Z" fill="white" stroke="#FC6B40"
                        stroke-width="2" />
                  </svg>
                  Завантажити в .ppt</a>
                  --}}
            </div>

            <div class="table-tools__legend">
               <div class="legend-item legend--green">
                  <span class="mark"> </span>
                  <span> — @lang('message.free_')</span>
               </div>
               <div class="legend-item legend--gray">
                  <span class="mark"> </span>
                  <span> — @lang('message.predzakaz')</span>
               </div>
               <div class="legend-item legend--yellow">
                  <span class="mark"> </span>
                  <span> — @lang('message.reserve')</span>
               </div>
               <div class="legend-item legend--red">
                  <span class="mark"> </span>
                  <span> — @lang('message.busy')</span>
               </div>
            </div>
         </div>

         <table class="constructions-table desktop">
            <thead>
               <tr>
                  <th class="sortable">
                     <div class="th-content">
                        <span>@lang('message.code')</span>
                        {{--
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                           <path d="M8 4.5H4L5.90476 2.5L8 4.5Z" fill="#3D445C" />
                           <path d="M8 7.5H4L5.90476 9.5L8 7.5Z" fill="#3D445C" />
                        </svg>
                        --}}
                     </div>
                  </th>

                  <th class="sortable">
                     <div class="th-content">
                        <span>@lang('message.type_')</span>
                        {{--
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                           <path d="M8 4.5H4L5.90476 2.5L8 4.5Z" fill="#3D445C" />
                           <path d="M8 7.5H4L5.90476 9.5L8 7.5Z" fill="#3D445C" />
                        </svg>
                        --}}
                     </div>
                  </th>

                  <th>
                     <div class="th-content">
                        <span>@lang('message.address_')</span>
                        {{--
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                           <path d="M8 4.5H4L5.90476 2.5L8 4.5Z" fill="#3D445C" />
                           <path d="M8 7.5H4L5.90476 9.5L8 7.5Z" fill="#3D445C" />
                        </svg>
                        --}}
                     </div>
                  </th>

                  <th>
                     <div class="th-content">
                        <span>Сторона</span>
                        {{--
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                           <path d="M8 4.5H4L5.90476 2.5L8 4.5Z" fill="#3D445C" />
                           <path d="M8 7.5H4L5.90476 9.5L8 7.5Z" fill="#3D445C" />
                        </svg>
                        --}}
                     </div>
                  </th>

                  <th>
                     <div class="th-content">
                        <span>@lang('message.light_')</span>
                        {{--
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                           <path d="M8 4.5H4L5.90476 2.5L8 4.5Z" fill="#3D445C" />
                           <path d="M8 7.5H4L5.90476 9.5L8 7.5Z" fill="#3D445C" />
                        </svg>
                        --}}
                     </div>
                  </th>

                  <th>
                     <div class="th-content">
                        <span>@lang('message.photo')</span>
                     </div>
                  </th>

                  <th>Мапа</th>

                  <th class="sortable">
                     <div class="th-content">
                        <span>@lang('message.employment_')</span>
                     </div>
                  </th>

                  <th class="sortable">
                     <div class="th-content">
                        <span>@lang('message.price_')</span>
                        {{--
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                           <path d="M8 4.5H4L5.90476 2.5L8 4.5Z" fill="#3D445C" />
                           <path d="M8 7.5H4L5.90476 9.5L8 7.5Z" fill="#3D445C" />
                        </svg>
                        --}}
                     </div>
                  </th>

                  <th></th>
               </tr>
            </thead>
            
            <tbody id="board-tbody" class="tbody"></tbody>
         </table>
 
         <div id="mobile-tables-container"></div>

         <div class="table-tools desktop">
            <div class="table-tools__export">
               <a class="table-tools__export-btn btn--xls" href="#">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24">
                     <path d="M12 16V4M12 16l-5-5m5 5l5-5M5 20h14" stroke="#f15a29" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  Завантажити в .xls </a>
               {{--
               <a class="table-tools__export-btn btn--pdf" href="#">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                     <path
                        d="M17.292 5.99121V16.667C17.2919 17.2747 17.0498 17.8574 16.6201 18.2871C16.1904 18.7168 15.6077 18.958 15 18.958H5C4.39229 18.958 3.80964 18.7168 3.37988 18.2871C2.95019 17.8574 2.70809 17.2747 2.70801 16.667V3.33301C2.70809 2.72534 2.95019 2.14258 3.37988 1.71289C3.80964 1.28322 4.39229 1.04199 5 1.04199H12.3418L17.292 5.99121ZM5 2.29199C4.72381 2.29199 4.45901 2.40143 4.26367 2.59668C4.0684 2.79195 3.95809 3.05686 3.95801 3.33301V16.667C3.95809 16.9431 4.0684 17.208 4.26367 17.4033C4.45901 17.5986 4.72381 17.708 5 17.708H15C15.2762 17.708 15.541 17.5986 15.7363 17.4033C15.9316 17.208 16.0419 16.9431 16.042 16.667V7.29199H11.042V2.29199H5ZM13.333 14.792H6.66699V13.542H13.333V14.792ZM13.333 11.458H6.66699V10.208H13.333V11.458ZM8.33301 8.125H6.66699V6.875H8.33301V8.125ZM12.292 6.04199H15.5752L12.292 2.75879V6.04199Z"
                        fill="#FC6B40" />
                  </svg>
                  Завантажити в .pdf</a>
               <a class="table-tools__export-btn btn--ppt" href="#">
                  <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                     <path
                        d="M16.5381 1.67773C17.5464 1.78031 18.334 2.63157 18.334 3.66699V11.334L18.3232 11.5381C18.2276 12.4792 17.4792 13.2276 16.5381 13.3232L16.334 13.334H10.75V15.917H14.166V17.417H5.83301V15.917H9.25V13.334H3.66699L3.46289 13.3232C2.52155 13.2278 1.77348 12.4793 1.67773 11.5381L1.66699 11.334V3.66699C1.66699 2.63134 2.45422 1.78 3.46289 1.67773L3.66699 1.66699H16.334L16.5381 1.67773ZM3.66699 3.16699C3.39085 3.16699 3.16699 3.39085 3.16699 3.66699V11.334C3.16734 11.6098 3.39107 11.834 3.66699 11.834H16.334C16.6096 11.8336 16.8336 11.6096 16.834 11.334V3.66699C16.834 3.39107 16.6098 3.16734 16.334 3.16699H3.66699Z"
                        fill="#FC6B40" />
                     <path d="M9.75 7.93262V7.06738L10.5 7.5L9.75 7.93262Z" fill="white" stroke="#FC6B40"
                        stroke-width="2" />
                  </svg>
                  Завантажити в .ppt</a>
               --}}
            </div>

            <div class="table-tools__legend">
               <div class="legend-item legend--green">
                  <span class="mark"> </span>
                  <span> — @lang('message.free_')</span>
               </div>
               <div class="legend-item legend--gray">
                  <span class="mark"> </span>
                  <span> — @lang('message.predzakaz')</span>
               </div>
               <div class="legend-item legend--yellow">
                  <span class="mark"> </span>
                  <span> — @lang('message.reserve')</span>
               </div>
               <div class="legend-item legend--red">
                  <span class="mark"> </span>
                  <span> — @lang('message.busy')</span>
               </div>
            </div>
         </div>


         <div class="pagination-container">
            <div class="pagination-info">
               <label for="rowsPerPage">Кількість строк:</label>
               <select id="rowsPerPage">
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
               </select>
               <span id="rangeDisplay">1–25 з {{$boards->count()}}</span>
            </div>

            <div class="pagination-controls">
               <button id="prevPage">&lt;</button>
               <div id="pages"></div>
               <button id="nextPage">&gt;</button>
            </div>
         </div>

         <!-- ____________________________________________ -->

         <label class="comments-label" for="proposal-comments">Коментарі</label>
         <textarea id="proposal-comments" placeholder="Коментар до замовлення..."></textarea>


         <div class="additionals-container">
            <div class="left-side">
               <b>Оберіть додаткові послуги:</b>

               <div class="options-wrapper">
                  <label class="option">
                     <input type="checkbox" hidden>
                     <span class="custom-checkbox">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                           <path fill-rule="evenodd" clip-rule="evenodd"
                              d="M5.24995 9.29798L11.3573 3.19067L11.976 3.80939L5.24995 10.5354L2.02393 7.30939L2.64264 6.69067L5.24995 9.29798Z"
                              fill="#FC6B40" />
                        </svg>
                     </span>
                     Розробка макета (вартість від 1000 грн.)
                  </label>

                  <label class="option">
                     <input type="checkbox" hidden>
                     <span class="custom-checkbox">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none">
                           <path fill-rule="evenodd" clip-rule="evenodd"
                              d="M5.24995 9.29798L11.3573 3.19067L11.976 3.80939L5.24995 10.5354L2.02393 7.30939L2.64264 6.69067L5.24995 9.29798Z"
                              fill="#FC6B40" />
                        </svg>
                     </span>
                     Друк постеру(ів) (постер 3x6м)
                  </label>
               </div>
            </div>

            <div class="right-side">
               <div class="sum-wrapper">
                  <p class="sum-text">обрано <span>12</span> на суму</p>
                  <p class="sum-number">52 200 ₴</p>
               </div>

               <label class="show-selected-label">
                  <input type="checkbox" hidden>
                  <span class="custom-checkbox"></span>
                  <span>Показати обрані</span>
               </label>

               <button class="order-submit-btn">Замовити</button>
            </div>


         </div>

         <!-- ____________________________________________ -->

         <p class="important-note">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
               <g clip-path="url(#clip0_1_2104)">
                  <path
                     d="M6 11C8.76142 11 11 8.76142 11 6C11 3.23858 8.76142 1 6 1C3.23858 1 1 3.23858 1 6C1 8.76142 3.23858 11 6 11Z"
                     stroke="#8B8F9D" stroke-width="0.75" stroke-linecap="round" stroke-linejoin="round" />
                  <path d="M6 8V6" stroke="#8B8F9D" stroke-width="0.75" stroke-linecap="round"
                     stroke-linejoin="round" />
                  <path d="M6 4H6.005" stroke="#8B8F9D" stroke-width="0.75" stroke-linecap="round"
                     stroke-linejoin="round" />
               </g>
               <defs>
                  <clipPath id="clip0_1_2104">
                     <rect width="12" height="12" fill="white" />
                  </clipPath>
               </defs>
            </svg> Важливо: Ціни вказані без врахування ПДВ та інших податків. Вартість включає лише одну копійку.
         </p>
         <p class="info-text">
            Якщо ви бажаєте внести зміни у перелік запропонованих конструкцій – зв’яжіться з менеджером, який врахує всі
            ваші зауваження та оновить пропозицію.
         </p>
         <p class="info-text">
            Оберіть конструкції які вас цікавлять, та додаткові послуги, натисніть кнопку “Підтвердити резерв” і наш
            менеджер самостійно зв’яжеться з вами для оформлення оренди та розміщення Вашої реклами
         </p>
      </section>
   </div>


   <!-- Order status modal window -->
   <div class="order-modal-backdrop">
      <div class="order-modal">
         <button class="close-order-modal-btn close-order-modal-btn--cross">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
               <path
                  d="M19.3313 4.6936L20.1799 5.54224L20.2854 5.64868L20.1799 5.75513L13.4973 12.4368L20.2854 19.2249L20.1799 19.3313L19.3313 20.1799L19.2249 20.2854L12.4368 13.4973L5.75513 20.1799L5.64868 20.2854L5.54224 20.1799L4.6936 19.3313L4.58813 19.2249L4.6936 19.1194L11.3752 12.4368L4.6936 5.75513L4.58813 5.64868L4.6936 5.54224L5.54224 4.6936L5.64868 4.58813L5.75513 4.6936L12.4368 11.3752L19.1194 4.6936L19.2249 4.58813L19.3313 4.6936Z"
                  fill="#3D445C" stroke="#3D445C" stroke-width="0.3" />
            </svg>
         </button>

         <div class="order-modal-content">
            <div class="icon-wrapper">
               <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 42 42" fill="none">
                  <path fill-rule="evenodd" clip-rule="evenodd"
                     d="M15.7501 27.8939L34.072 9.57202L35.9282 11.4282L15.7501 31.6063L6.07202 21.9282L7.92818 20.072L15.7501 27.8939Z"
                     fill="#FC6B40" />
               </svg>
            </div>

            <p class="text-strong">Дякуємо, ваше замовлення прийнято<br> та вже опрацьовується</p>
            <p class="text-small">Найближчим часом з Вами зв’яжеться наш менеджер</p>

            <button class="close-order-modal-btn close-order-modal-btn--regular">Ок</button>
         </div>
      </div>

      <div class="order-modal">
         <button class="close-order-modal-btn  close-order-modal-btn--cross">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
               <path
                  d="M19.3313 4.6936L20.1799 5.54224L20.2854 5.64868L20.1799 5.75513L13.4973 12.4368L20.2854 19.2249L20.1799 19.3313L19.3313 20.1799L19.2249 20.2854L12.4368 13.4973L5.75513 20.1799L5.64868 20.2854L5.54224 20.1799L4.6936 19.3313L4.58813 19.2249L4.6936 19.1194L11.3752 12.4368L4.6936 5.75513L4.58813 5.64868L4.6936 5.54224L5.54224 4.6936L5.64868 4.58813L5.75513 4.6936L12.4368 11.3752L19.1194 4.6936L19.2249 4.58813L19.3313 4.6936Z"
                  fill="#3D445C" stroke="#3D445C" stroke-width="0.3" />
            </svg>
         </button>
         <div class="order-modal-content">
            <div class="icon-wrapper">
               <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 42 42" fill="none">
                  <path fill-rule="evenodd" clip-rule="evenodd"
                     d="M11.9282 11.9282L20.9999 21L30.0718 11.9282L31.928 13.7844L22.8561 22.8561L31.928 31.928L30.0718 33.7842L20.9999 24.7124L11.9282 33.7842L10.072 31.928L19.1437 22.8561L10.072 13.7844L11.9282 11.9282Z"
                     fill="#FC6B40" />
               </svg>

            </div>

            <p class="text-strong">Сталася помилка при оформленні <br> замовлення</p>
            <p class="text-small">Спробуйте, будь-ласка, пізніше</p>

            <button class="close-order-modal-btn close-order-modal-btn--regular">Ок</button>
         </div>
      </div>
   </div>


   <!--Map modal window desktop -->
   <div class="map-modal-backdrop">
      <div class="map-modal">
         <button class="close-map-modal-btn close-map-modal-btn--cross">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
               <path
                  d="M19.3313 4.6936L20.1799 5.54224L20.2854 5.64868L20.1799 5.75513L13.4973 12.4368L20.2854 19.2249L20.1799 19.3313L19.3313 20.1799L19.2249 20.2854L12.4368 13.4973L5.75513 20.1799L5.64868 20.2854L5.54224 20.1799L4.6936 19.3313L4.58813 19.2249L4.6936 19.1194L11.3752 12.4368L4.6936 5.75513L4.58813 5.64868L4.6936 5.54224L5.54224 4.6936L5.64868 4.58813L5.75513 4.6936L12.4368 11.3752L19.1194 4.6936L19.2249 4.58813L19.3313 4.6936Z"
                  fill="#FFF" stroke="#FFF" stroke-width="0.3" />
            </svg>
         </button>


         <!-- <img class="modal-map map-widget" src="https://salon.ru/storage/thumbs/gallery/5/4178/5000_5000_s193.jpg"
            alt="карта"> -->
         <img class="modal-map modal-photo" alt="фото билборда"
            src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ3e_y-BTn1ejQlCqMApYP2ucgmdWSa5gb3zw&s">
         <div class="map-modal-content-wrapper">
            <p class="location-text">Івано-Франківськ , Тараса Шевченка, Березівка. Виїзд з Березівки в Тисменичани, ст.
               А - до Буковелю 81 км
            </p>

            <div class="map-modal-price-checkbox-wrapper">
               <div class="map-modal-price-wrapper">
                  <span class="text">вартість оренди, міс.</span>
                  <span class="price">52 200 ₴</span>
               </div>

               <label class="select-construction-label">
                  <input type="checkbox" class="select-construction-checkbox photo-modal-desktop-checkbox"
                     hidden>
                  <div class="select-button">
                     <span class="to-choose map-modal-checkbox">Обрати</span>
                     <span class="choosen map-modal-checkbox">Обрано</span>
                  </div>
               </label>
            </div>
         </div>
      </div>


   </div>

   <!--Map modal window mobile -->
   <div class="map-modal-backdrop-mobile">
      <div class="map-modal-mobile">
         <div class="map-modal-mobile-container">
            <div class="header-wrapper">
               <h3><span>351</span>, <span>Борд</span></h3>
               <button class="close-map-modal-btn-mobile close-map-modal-btn--cross-mobile">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                     <path
                        d="M19.3313 4.6936L20.1799 5.54224L20.2854 5.64868L20.1799 5.75513L13.4973 12.4368L20.2854 19.2249L20.1799 19.3313L19.3313 20.1799L19.2249 20.2854L12.4368 13.4973L5.75513 20.1799L5.64868 20.2854L5.54224 20.1799L4.6936 19.3313L4.58813 19.2249L4.6936 19.1194L11.3752 12.4368L4.6936 5.75513L4.58813 5.64868L4.6936 5.54224L5.54224 4.6936L5.64868 4.58813L5.75513 4.6936L12.4368 11.3752L19.1194 4.6936L19.2249 4.58813L19.3313 4.6936Z"
                        fill="#3D445C" stroke="#3D445C" stroke-width="0.3" />
                  </svg>
               </button>
            </div>
         </div>

         <div class="map-modal-mobile-container">
            <div class="tabs-wrapper">
               <label class="tab-label">
                  <input type="radio" name="map-tab" class="photo-tab" id="photoTab" checked hidden>
                  <span>Фото</span>
               </label>
               <label class="tab-label">
                  <input type="radio" name="map-tab" class="map-tab" id="mapTab" hidden>
                  <span>Мапа</span>
               </label>
            </div>
         </div>

         <img id="mapImage" class="mobile-modal-map map-widget"
            src="https://salon.ru/storage/thumbs/gallery/5/4178/5000_5000_s193.jpg" alt="карта">

         <img id="photoImage" class="mobile-modal-map modal-photo"
            src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ3e_y-BTn1ejQlCqMApYP2ucgmdWSa5gb3zw&s"
            alt="фото билборда">


         <div class="map-modal-mobile-container">
            <p class="location-text">Івано-Франківськ , Тараса Шевченка, Березівка. Виїзд з Березівки в Тисменичани, ст.
               А - до Буковелю 81 км
            </p>

            <table class="map-modal-mobile-info-table">
               <tbody>
                  <tr>
                     <td class="td-name">код</td>
                     <td class="td-info">351</td>
                  </tr>

                  <tr>
                     <td class="td-name">тип</td>
                     <td class="td-info">Борд</td>
                  </tr>

                  <tr>
                     <td class="td-name">сторона</td>
                     <td class="td-info">А</td>
                  </tr>

                  <tr>
                     <td class="td-name">підсвітка</td>
                     <td class="td-info"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                           viewBox="0 0 18 18" fill="none">
                           <path d="M6.75 13.5H11.25" stroke="#4FB14B" stroke-width="1.5" stroke-linecap="round"
                              stroke-linejoin="round" />
                           <path d="M7.5 16.5H10.5" stroke="#4FB14B" stroke-width="1.5" stroke-linecap="round"
                              stroke-linejoin="round" />
                           <path
                              d="M11.3175 10.5C11.4525 9.765 11.805 9.195 12.375 8.625C12.7371 8.29163 13.0246 7.88537 13.2185 7.43294C13.4124 6.98051 13.5083 6.49216 13.5 6C13.5 4.80653 13.0259 3.66193 12.182 2.81802C11.3381 1.97411 10.1935 1.5 9 1.5C7.80653 1.5 6.66193 1.97411 5.81802 2.81802C4.97411 3.66193 4.5 4.80653 4.5 6C4.5 6.75 4.6725 7.6725 5.625 8.625C6.16804 9.12155 6.5385 9.77839 6.6825 10.5"
                              stroke="#4FB14B" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg></td>
                  </tr>
               </tbody>
            </table>

            <div class="availability-calendar-wrapper">
               <h3 class="calendar-title">Календар занятості</h3>
               <div class="availability-calendar-dates-wrapper">
                  <span class="booked">05</span>
                  <span class="available">06</span>
                  <span class="available">07</span>
                  <span class="available">08</span>
                  <span class="available">09</span>
                  <span class="available">10</span>
                  <span class="available">11</span>
                  <span class="available">12</span>
                  <span>01</span>
                  <span>02</span>
                  <span>03</span>
                  <span>04</span>
               </div>
            </div>

            <div class="map-modal-mobile-price-wrapper">
               <span class="text">вартість оренди, міс.</span>
               <span class="price">52 200 ₴</span>
            </div>

            <label class="select-construction-label-mobile">
               <input type="checkbox" class="select-construction-mobile-checkbox" checked name="constructions[]"
                  value="351" hidden>
               <div class="select-button-mobile">
                  <span class="to-choose map-modal-checkbox-mobile">Обрати</span>
                  <span class="choosen map-modal-checkbox-mobile">Обрано</span>
               </div>
            </label>

         </div>
      </div>
   </div>



  @include('add.modals')
  <div id='show-map-modal' class="modal">
     <div id="map-modal-board"></div>
  </div>
<style>
#manager-map{
    height:600px;
}
.send-to-manager{
    padding: 20px;
    background:#fff;
}
.btn-style{
    padding:0 15px;
}
h1.title{
    margin-bottom:0px;
}
.basket-search-address{
    cursor: pointer;
}
</style>
<script>

@if($coords)
var coords = {!!$coords!!};
@endif

@if($places)
var search_places = {!! json_encode($places, JSON_UNESCAPED_UNICODE ) !!};
@endif
@if($places_data)
var search_places_data = {!! json_encode($places_data, JSON_UNESCAPED_UNICODE ) !!};
@endif
var markerArea;
</script>

  <!-- google map API -->
  @if(App::getLocale() == 'ua')
        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDz2s29ChsjKNAecWarcoSx76by6WDSiXw&language=uk&region=UA&libraries=places,drawing"></script>
        @else
        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDz2s29ChsjKNAecWarcoSx76by6WDSiXw&language=ru&libraries=places,drawing"></script>
        @endif

  {{--
    <script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>

  <script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>
  --}}
  <script src="/assets/js/markerclusterer.index.min.js"></script>
  
<!--  
  <script src="/assets/js/jquery/jquery.js"></script>
  <script src="/assets/js/jquery/jquery-ui.min.js"></script>
  <script src="/assets/js/jquery.ui.touch-punch.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>

  <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
  
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" defer></script>
<link rel="stylesheet" as="style" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"/>

  <script type="text/javascript" src="/assets/js/randomColor.js"></script>
  <script type="text/javascript" src="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js"></script>

  <script src="/assets/js/scripts.js"></script>
-->
@include('add.footer')  
  <!--  -->
  <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" defer></script>
  <link rel="stylesheet" as="style" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
  <script src="/assets/js/manager_map.js?t=20240703-1"></script>
  

<style>
#result-search-list .result-list .table-result .thead, 
#result-search-list .result-list .table-result .tbody{
    display:block;
}
#result-search-list .result-list .table-result .thead .tr, 
#result-search-list .result-list .table-result .tbody .tr{
    display:flex;
    flex-flow: row;
    align-content: flex-start
}
#result-search-list .result-list .table-result .thead .tr{
    align-content:center;
}
#result-search-list .result-list .table-result .thead .tr .td, 
#result-search-list .result-list .table-result .tbody .tr .td{
    flex: 1;
}
#result-search-list .result-list .table-result .thead .tr .td.td-checkbox, 
#result-search-list .result-list .table-result .tbody .tr .td.td-checkbox{
    flex: 0.5;
}
#result-search-list .result-list .table-result .thead .tr .td.td-adress, 
#result-search-list .result-list .table-result .tbody .tr .td.td-adress{
    flex: 5;
}
.basket-search-address{
    background: rgba(255,255,0,0.3);
    border-bottom: 1px solid #dee5ec;
    padding: 9px 6px 8px;
}

.field-container{
	/*border-bottom: solid 1px #e6e9ec;*/
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
}	
.field-container.hide{
	display: none;
}
.contact-form .input-block{
	margin-right: 15px;
	margin-bottom: 15px;
	position: relative;
}
.contact-form .input-block:nth-child(3n){
	margin-right: 0;
}
.contact-form .input-block label{
	display: block;
	font-family: Helvetica Neue;
	font-style: normal;
	font-weight: 500;
	font-size: 13px;
	line-height: 16px;
	color: #ADB0B9;
	margin-bottom: 5px;
}
.contact-form .input-block input{
	background: #F6F7F9;
	border: 1px solid #CDD4D9;
	box-sizing: border-box;
	border-radius: 3px;
	width: 295px;
	height: 42px;

	font-family: Helvetica Neue;
	font-style: normal;
	font-weight: 500;
	font-size: 13px;
	line-height: 16px;
	color: #3D445C;

	padding: 0 11px;
}

.map-billboard-card .map-card-left .map-card-slider {
    width: 100%;
    height: 152px;
}
.map-card-left .map-card-slider.not-slick > img, 
.map-billboard-card .map-card-left .map-card-slider.not-slick > img, 
.map-card-left .map-card-slider.not-slick > img, 
.map-card-left .no-slick > img {
    height: 100%;
    object-fit: contain;
}

.map__popup-slider > .slick-list.draggable .slick-track {
    margin-left: 40px;
}

@media screen and (max-width:560px){
    #result-search-list .result-list .table-result .thead{
        display: none;
    }
    #result-search-list .result-list .table-result .tbody .tr{
        display: flex;
        flex-direction: column;
        margin: 22px 0 0;
        padding: 0 20px 22px;
        border-bottom: 1px solid #dee5ec;
    }
    #result-search-list .result-list .table-result .tbody .td.td-checkbox{
        display:block !important;
    }
}

/* Yara CSS */
/* Временный для разработки стьиль для контейнера */
body {
   margin: 0;
   padding: 0;
   font-family: 'Helvetica Neue', Helvetica, 'helvetica', Arial, sans-serif;
   font-size: 14px;
   color: #3e445b;
   -webkit-font-smoothing: antialiased;
   -moz-osx-font-smoothing: grayscale;
}

body.modal-open {
   overflow: hidden;
}

.container-base {
   max-width: 960px;
   padding-right: 0;
   padding-left: 0;
}

.container {
   width: 100%;
   padding-right: 16px;
   padding-left: 16px;
   margin-right: auto;
   margin-left: auto;
}

@media (min-width: 576px) {
   .container {
      max-width: 540px;
   }
}

@media (min-width: 768px) {
   .container {
      max-width: 720px;
   }
}

@media (min-width: 992px) {
   .container {
      max-width: 960px;
   }
}

@media (min-width: 1200px) {
   .container {
      max-width: 1140px;
   }
}

/* @font-face {
   font-family: "Helvetica Neue";
   src: url("./font/HelveticaNeueCyr-Roman.woff") format("woff");
   font-weight: normal;
   font-style: normal;
   font-display: swap;
} */

* {
   box-sizing: border-box;
}

/* ----------- */

.cd-section {
   padding: 24px 0;
   margin: 0;
   background-color: #FFF;
   font-family: "Helvetica Neue";
}

.cd-wrapper {
   width: 100%;
   display: flex;
   align-items: flex-start;
   flex-direction: row;
   padding: 32px;
   background-color: #F9F9FA;
}

@media (max-width: 768px) {
   .cd-wrapper {
      flex-direction: column;
      padding: 16px;
   }
}

.cd-client-data-wrapper {
   width: 30%;
}

@media (max-width: 768px) {
   .cd-client-data-wrapper {
      width: 100%;
      flex-direction: column;
   }
}

.cd-divider-wrapper {
   width: 10%;
   display: flex;
   align-items: center;
   justify-content: center;
}

@media (max-width: 768px) {
   .cd-divider-wrapper {
      width: 100%;
      height: 14px;
   }
}

.cd-divider {
   width: 1px;
   height: 178px;
   background-color: #E8EBF1;
   flex-shrink: 0;
}

@media (max-width: 768px) {
   .cd-divider {
      width: 100%;
      height: 1px;
   }
}

.cd-manager-data-wrapper {
   width: 60%;
}

@media (max-width: 768px) {
   .cd-manager-data-wrapper {
      width: 100%;
   }
}

.cd-proposal-to-title {
   margin-top: 0;
   margin-bottom: 32px;
   color: #3D445C;
   font-size: 24px;
   font-style: normal;
   font-weight: 400;
   line-height: 24px;
}

@media (max-width: 768px) {
   .cd-proposal-to-title {
      margin-bottom: 20px;
      font-size: 20px;
   }
}

.cd-client-info-wrapper {
   display: flex;
   justify-content: flex-start;
   align-items: center;
   gap: 5px;
}

.cd-clien-name {
   margin-bottom: 20px;
   color: #3D445C;
   font-size: 16px;
   font-style: normal;
   font-weight: 500;
   line-height: 130%;
   text-transform: uppercase;
   text-align: left;
}

@media (max-width: 768px) {
   .cd-clien-name {
      margin-bottom: 16px;
      font-size: 18px;
   }
}

.cd-client-info {
   color: #8B8F9D;
   font-size: 14px;
   font-style: normal;
   font-weight: 400;
   line-height: 130%;
   text-decoration: none;
}

.cd-client-info-wrapper {
   margin-bottom: 14px;
}

@media (max-width: 768px) {
   .cd-client-info-wrapper {
      margin-bottom: 16px;
   }
}

.cd-client-info-wrapper:last-of-type {
   margin-bottom: 0;
}

.cd-manager-title {
   margin-bottom: 20px;
   margin-top: 18px;
   color: #3D445C;
   font-size: 18px;
   font-style: normal;
   font-weight: 400;
   line-height: 24px;
   text-align: left;
}

.cd-manager-inner-title {
   margin: 0;
   padding: 0 0 12px 0;
   color: #8B8F9D;
   font-size: 14px;
   font-style: normal;
   font-weight: 400;
   line-height: 130%;
}

.cd-manager-inner-wrapper {
   display: flex;
   justify-content: flex-start;
   align-items: flex-start;
}

.cd-manager-titles,
.cd-manager-data {
   flex-basis: 50%;
}

.cd-manager-inner-text {
   margin: 0;
   padding: 0 0 12px 0;
   color: var(--black-100, #3D445C);
   font-size: 14px;
   font-style: normal;
   font-weight: 400;
   line-height: 130%;
}

.cd-manager-inner-phone {
   color: #FC6B40;
}

.cd-manager-phone-wrapper {
   display: flex;
   justify-content: flex-start;
   align-items: center;
   gap: 5px;
}

.socials-container {
   display: flex;
   justify-content: flex-start;
   align-items: center;
   gap: 10px;
}

/* proposal constructions section CSS */
.proposal-title {
   margin-top: 80px;
   margin-bottom: 16px;
   color: #3D445C;
   font-size: 34px;
   font-style: normal;
   font-weight: 400;
   line-height: normal;
}

@media (max-width: 768px) {
   .proposal-title {
      margin-top: 48px;
      margin-bottom: 20px;
      font-size: 26px;
   }
}

.proposal-title sup {
   margin-left: 11px;
   color: #8B8F9D;
   font-size: 18px;
   font-style: normal;
   font-weight: 400;
   line-height: 24px;
}

.proposal-constructions-section {
   padding-bottom: 80px;
   border-bottom: 1px solid #CDD4D9;
}

@media (max-width: 768px) {
   .proposal-constructions-section {
      padding-bottom: 48px;
   }
}

.proposal-constructions-section--text {
   margin-bottom: 16px;
   color: #3D445C;
   font-size: 13px;
   font-style: normal;
   font-weight: 400;
   line-height: 18px;
}

.proposal-constructions-section--text>.date {
   color: #FC6B40;
}

.proposal-constructions-section .table-tools {
   width: 100%;
   display: flex;
   justify-content: space-between;
   /* align-items: center; */
   padding-bottom: 20px;
   border-bottom: 1px solid #E8EBF1;
}

@media (max-width: 768px) {
   .proposal-constructions-section .table-tools {
      flex-direction: column;
      gap: 20px;
   }
}

@media (max-width: 768px) {
   .proposal-constructions-section .table-tools.desktop {
      display: none;
   }
}

.table-tools__export {
   display: flex;
   justify-content: flex-start;
   align-items: center;
   gap: 24px;
}

@media (max-width: 768px) {
   .table-tools__export {
      gap: 20px;
      flex-wrap: wrap;
   }
}

.table-tools__export-btn {
   display: flex;
   justify-content: flex-start;
   align-items: center;
   gap: 5px;
   color: #FC6B40;
   /* link */
   font-family: 'Helvetica Neue', Helvetica, 'helvetica', Arial, sans-serif;
   font-size: 13px;
   font-style: normal;
   font-weight: 500;
   line-height: 130%;
   text-decoration: none;
}

.table-tools__legend {
   display: flex;
   justify-content: flex-start;
   align-items: center;
   gap: 20px;
}

@media (max-width: 768px) {
   .table-tools__legend {
      flex-wrap: wrap;
   }
}

.legend-item {
   display: flex;
   justify-content: flex-start;
   align-items: center;
   color: #3D445C;
   font-size: 11px;
   font-style: normal;
   font-weight: 400;
   line-height: 130%;
}

.legend-item .mark {
   width: 8px;
   height: 8px;
   border-radius: 50%;
   background-color: #FC6B40;
   margin-right: 3px;
   flex-grow: 0;
   flex-shrink: 0;
}

.legend--gray .mark {
   background-color: #CDD4D9;
}

.legend--yellow .mark {
   background-color: #F2C94C;
}

.legend--green .mark {
   background-color: #4FB14B;
}

.constructions-table {
   width: 100%;
   min-width: 720px;
   margin-bottom: 24px;
   text-align: left;
   /*overflow: hidden;*/
   color: #3D445C;
   /*text-overflow: ellipsis;*/
   font-size: 13px;
   font-style: normal;
   font-weight: 400;
   line-height: 18px;
   border-collapse: collapse;
}

@media (max-width: 768px) {
   .constructions-table.desktop {
      display: none;
   }
}

.constructions-table tr {
   min-height: 80px;
}

.constructions-table .choosen {
   background-color: #FFF0EC;
}

.constructions-table th,
.constructions-table td {
   padding: 16px 16px 16px 0;
   border-bottom: 1px solid #E8EBF1;
   overflow: hidden;
   color: #3D445C;
   text-overflow: ellipsis;
   font-size: 13px;
   font-style: normal;
   font-weight: 400;
   line-height: 18px;
}

.constructions-table .th-content {
   display: inline-flex;
   align-items: center;
   justify-content: flex-start;
   gap: 2px;
}

.constructions-table th:first-child,
.constructions-table td:first-child {
   width: 6.8%;
   padding-left: 16px;
}

.constructions-table th:nth-child(2),
.constructions-table td:nth-child(2) {
   width: 6.8%;
}

.constructions-table th:nth-child(3),
.constructions-table td:nth-child(3) {
   width: 28.5%;
}

.constructions-table th:nth-child(4),
.constructions-table td:nth-child(4) {
   width: 7%;
}

.constructions-table th:nth-child(5),
.constructions-table td:nth-child(5) {
   width: 7.5%;
}

.constructions-table th:nth-child(6),
.constructions-table td:nth-child(6) {
   width: 9%;
}

.constructions-table th:nth-child(7),
.constructions-table td:nth-child(7) {
   width: 4.5%;
}

.constructions-table th:nth-child(8),
.constructions-table td:nth-child(8) {
   width: 10.9%;
}

.constructions-table th:nth-child(9),
.constructions-table td:nth-child(9) {
   width: 9.2%;
}

.constructions-table th:nth-child(10),
.constructions-table td:nth-child(10) {
   width: 9.8%;
}


.constructions-table th {
   color: #3D445C;
   font-size: 11px;
   font-style: normal;
   font-weight: 500;
   line-height: 11px;
   text-transform: lowercase;
   cursor: pointer;
}

.constructions-table .construction-photo {
   width: 100%;
   height: 48px;
   object-fit: cover;
   cursor: zoom-in;
}

.construction-photo-wrapper,
.construction-photo-wrapper-mobile {
   position: relative;
}

.construction-photo-wrapper .zoom-icon-wrapper,
.construction-photo-wrapper-mobile .zoom-icon-wrapper {
   position: absolute;
   top: 2px;
   right: 3px;
   display: flex;
   justify-content: center;
   align-items: center;
   width: 20px;
   height: 20px;
   border-radius: 50%;
   background-color: #FFF0EC;
   backdrop-filter: blur(6px);
   transition: background-color 0.2s ease;
   cursor: zoom-in;
}

.construction-photo-wrapper:hover .zoom-icon-wrapper,
.construction-photo-wrapper:focus .zoom-icon-wrapper,
.construction-photo-wrapper:active .zoom-icon-wrapper,
.construction-photo-wrapper-mobile:hover .zoom-icon-wrapper,
.construction-photo-wrapper-mobile:focus .zoom-icon-wrapper,
.construction-photo-wrapper-mobile:active .zoom-icon-wrapper {
   background-color: #FC6B40;
}

.construction-photo {
   display: block;
}

.construction-photo-wrapper .zoom-icon {
   color: #FC6B40;
   transition: color 0.2s ease;
}

.construction-photo-wrapper:hover .zoom-icon,
.construction-photo-wrapper:focus .zoom-icon,
.construction-photo-wrapper:active .zoom-icon,

.construction-photo-wrapper-mobile:hover .zoom-icon,
.construction-photo-wrapper-mobile:focus .zoom-icon,
.construction-photo-wrapper-mobile:active .zoom-icon {
   color: #fff;
}

.constructions-table .show-on-map {
   margin: 0;
   padding: 5px 10px 5px 2px;
   display: flex;
   justify-content: center;
   align-items: center;
   border: none;
   background-color: transparent;
   cursor: pointer;
}

.progress-bar {
   width: 100%;
   max-width: 210px;
   height: 4px;
   background-color: #E8EBF1;
   border-radius: 2px;
   overflow: hidden;
}

.progress-fill {
   width: 60%;
   max-width: 210px;
   height: 100%;
   background-color: #4caf50;
   border-radius: 4px 0 0 4px;
}

.constructions-table .price {
   display: flex;
   justify-content: flex-start;
   align-items: center;
   gap: 2px;
   color: #3D445C;
   font-size: 13px;
   font-style: normal;
   font-weight: 700;
   line-height: 130%;
}

.constructions-table.desktop .select-construction-label {
  width:100%;
}
.constructions-table-mobile .select-construction-label {
  width:100%;
}
/* .select-button {
  width:100%;
} */
.select-button .to-choose {
   display: flex;
   /* width: 100%; */
   height: 30px;
   justify-content: center;
   align-items: center;
   border: none;
   border-radius: 4px;
   background-color: #FC6B40;
   color: #FFF;
   font-size: 13px;
   font-style: normal;
   font-weight: 700;
   line-height: 130%;
   transition: all 0.1s ease-in-out;
   cursor: pointer;
}

.select-button .choosen {
   display: none;
   /* width: 100%; */
   height: 30px;
   justify-content: center;
   align-items: center;
   font-style: normal;
   font-weight: 700;
   line-height: 130%;
   transition: all 0.1s ease-in-out;
   cursor: pointer;
   border-radius: 4px;
   border: 1px solid #CDD4D9;
   background-color: #FFF;
   font-size: 13px;
   color: #3D445C;
}

.select-construction-label:has(.select-construction-checkbox:checked) .to-choose {
   display: none;
}

.select-construction-label:has(.select-construction-checkbox:checked) .choosen {
   display: flex;
}

.constructions-table .lighting {
   stroke: #ADB0B9;
}

.constructions-table .lighting.with-light {
   stroke: #4FB14B;
}

tr.selected {
   background-color: #FFF0EC;
}

/* Pagination row CSS */

.pagination-container {
   display: flex;
   justify-content: space-between;
   align-items: center;
   margin: 20px 0 32px;
}

@media (max-width: 768px) {
   .pagination-container {
      flex-direction: column;
      justify-content: flex-start;
      align-items: flex-start;
   }
}

.pagination-info {
   display: flex;
   align-items: center;
   gap: 12px;
   color: #3D445C;
   font-size: 13px;
   font-style: normal;
   font-weight: 400;
   line-height: 18px;
}

@media (max-width: 768px) {
   .pagination-info {
      width: 100%;
      margin-bottom: 20px;
   }
}

#rowsPerPage {
   display: flex;
   width: 56px;
   height: 38px;
   padding: 8px 6px 8px 8px;
   margin-right: 24px;
   justify-content: space-between;
   align-items: center;
   border-radius: 3px;
   border: 1px solid #CDD4D9;
   background-color: #F6F7F9;
   font-size: 13px;
   font-weight: 500;
   line-height: 130%;
}

@media (max-width: 768px) {
   #rangeDisplay {
      margin-left: auto;
   }
}

.pagination-controls {
   display: flex;
   align-items: center;
   gap: 8px;
}

@media (max-width: 768px) {
   .pagination-controls {
      width: 100%;
      justify-content: center;
   }
}

.pagination-controls button {
   width: 38px;
   height: 38px;
   display: flex;
   justify-content: center;
   align-items: center;
   flex-shrink: 0;
   flex-grow: 0;
   color: #3D445C;
   font-size: 13px;
   font-style: normal;
   font-weight: 700;
   border-radius: 4px;
   background-color: #FFF;
   cursor: pointer;
   border: none;
}

.page-button.active {
   background-color: #4FB14B;
   color: #FFF;
}

.pagination-controls #prevPage,
.pagination-controls #nextPage {
   border: 1px solid #CDD4D9;
}

@media (max-width: 768px) {
   .pagination-controls #prevPage {
      margin-right: 17px;
   }
}

@media (max-width: 768px) {
   .pagination-controls #nextPage {
      margin-left: 17px;
   }
}

@media (max-width: 355px) {
   .pagination-controls #prevPage {
      margin-right: 5px;
   }
}

@media (max-width: 355px) {
   .pagination-controls #nextPage {
      margin-left: 5px;
   }
}

.pagination-controls #prevPage:disabled,
.pagination-controls #nextPage:disabled {
   opacity: 0.3;
}

.pagination-controls #pages {
   display: flex;
   align-items: center;
   gap: 8px;
}

.pagination-controls button:hover,
.pagination-controls button:focus,
.pagination-controls button:active {
   border: 1px solid #4FB14B;
}

.proposal-constructions-section .important-note {
   display: flex;
   align-items: center;
   gap: 6px;
   margin-bottom: 8px;
   color: #8B8F9D;
   font-size: 11px;
   font-style: normal;
   font-weight: 400;

}

.proposal-constructions-section .info-text {
   margin-top: 0;
   margin-bottom: 8px;
   color: #3D445C;
   font-size: 13px;
   font-style: normal;
   font-weight: 400;
   line-height: 18px;
}

/* ---------------- */

.comments-label {
   display: block;
   margin-bottom: 4px;
   color: #CDD4D9;
   font-size: 13px;
   font-style: normal;
   font-weight: 500;
}

#proposal-comments {
   width: 100%;
   box-sizing: border-box;
   height: 69px;
   margin-bottom: 20px;
   padding: 14px 13px;
   border-radius: 4px;
   border: 1px solid #CDD4D9;
   background-color: #F6F7F9;
   resize: none;
   color: #3D445C;
   font-size: 13px;
   font-style: normal;
   font-weight: 500;
   line-height: 130%;
}

@media (max-width: 355px) {
   #proposal-comments {
      height: 90px;
   }
}

@media (max-width: 768px) {
   #proposal-comments {
      margin-bottom: 24px;
   }
}

#proposal-comments::placeholder {
  font-family: 'Helvetica Neue', Helvetica, 'helvetica', Arial, sans-serif;
   color: #3D445C;
   font-size: 13px;
   font-style: normal;
   font-weight: 500;
   line-height: 130%;
}

.additionals-container {
   display: flex;
   width: 100%;
   box-sizing: border-box;
   justify-content: space-between;
   align-items: center;
   margin-bottom: 24px;
   padding: 24px 32px;
   background-color: #F9F9FA;
   border-radius: 4px;
}

@media (max-width: 768px) {
   .additionals-container {
      padding: 16px 16px;
      flex-direction: column;
      gap: 32px;
   }
}

.additionals-container .left-side {
   display: flex;
   flex-direction: column;
   gap: 9px;
}

@media (max-width: 768px) {
   .additionals-container .left-side {
      gap: 12px;
   }
}

.additionals-container .options-wrapper {
   display: flex;
   justify-content: flex-start;
   align-items: center;
   gap: 16px;
   flex-wrap: wrap;
}

@media (max-width: 768px) {
   .additionals-container .options-wrapper {
      gap: 12px;
   }
}

.additionals-container .option {
   display: flex;
   justify-content: flex-start;
   align-items: center;
   gap: 12px;
   flex-wrap: nowrap;
   cursor: pointer;
   color: #3D445C;
   font-size: 13px;
   font-style: normal;
   font-weight: 400;
   line-height: 18px;
}

@media (max-width: 768px) {
   .additionals-container .right-side {
      justify-content: space-between;
      gap: 0;
   }
}

.additionals-container .custom-checkbox {
   display: flex;
   justify-content: center;
   align-items: center;
   width: 20px;
   height: 20px;
   border-radius: 4px;
   border: 1px solid #CDD4D9;
   background: #FFF;
}

.additionals-container input[type="checkbox"]:checked+.custom-checkbox {
   border: 1px solid #FC6B40;
}

.additionals-container input[type="checkbox"]+.custom-checkbox>svg {
   visibility: hidden;
}

.additionals-container input[type="checkbox"]:checked+.custom-checkbox>svg {
   visibility: visible;
}

.additionals-container .right-side {
   display: flex;
   justify-content: flex-end;
   align-items: center;
   gap: 12px;
}

@media (max-width: 768px) {
   .additionals-container .right-side {
      width: 100%;
      justify-content: space-between;
      gap: 0;
      flex-wrap: wrap;
      row-gap: 20px;
   }
}

.right-side .sum-wrapper {
   display: flex;
   flex-direction: column;
   align-items: flex-end;
   gap: 4;
}

@media (max-width: 768px) {
   .right-side .sum-wrapper {
      align-items: flex-start;
   }
}

.sum-wrapper p {
   margin: 0;
   padding-right: 12px;
}

@media (max-width: 768px) {
   .sum-wrapper p {
      padding-right: 0;
   }
}

.sum-wrapper .sum-text {
   color: #8B8F9D;
   text-align: right;
   font-size: 13px;
   font-style: normal;
   font-weight: 400;
   line-height: 18px;
}

.sum-wrapper .sum-text>span {
   color: #3D445C;
   font-size: 13px;
   font-style: normal;
   font-weight: 500;
   line-height: 130%;
}

.sum-wrapper .sum-number {
   color: #3D445C;
   text-align: right;
   font-size: 18px;
   font-style: normal;
   font-weight: 500;
   line-height: 20px;
}

.show-selected-label {
   display: flex;
   flex-direction: column;
   justify-content: center;
   align-items: center;
   gap: 2px;
   cursor: pointer;
}

@media (max-width: 768px) {
   .show-selected-label {
      align-items: flex-start;
      margin-right: 7%;
   }
}

.show-selected-label .custom-checkbox {
   display: block;
   width: 40px;
   height: 24px;
   background-color: #E8EBED;
   border-radius: 12px;
   position: relative;
   transition: all 0.3s;
}

.show-selected-label .custom-checkbox::before {
   content: "";
   position: absolute;
   top: 2px;
   left: 2px;
   width: 18px;
   height: 18px;
   background: white;
   border-radius: 50%;
   transition: transform 0.3s ease;
   box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.show-selected-label input:checked+.custom-checkbox::before {
   left: 16px;
}

.show-selected-label input:checked+.custom-checkbox {
   background-color: #FC6B40;
}

.additionals-container .order-submit-btn {
   display: flex;
   padding: 13px 24px;
   justify-content: center;
   align-items: center;
   gap: 10px;
   border-radius: 4px;
   background-color: #FC6B40;
   border: none;
   color: #FFF;

   font-size: 13px;
   font-style: normal;
   font-weight: 700;
   cursor: pointer;
}

@media (max-width: 768px) {
   .additionals-container .order-submit-btn {
      width: 100%;
      max-width: 296px;
      margin: 0 auto;
   }
}

/* Order modal CSS */

.order-modal-backdrop {
   position: fixed;
   inset: 0;
   background: rgba(0, 0, 0, 0.7);
   display: none;
   flex-direction: column;
   align-items: center;
   justify-content: center;
   z-index: 1000;
}

.order-modal {
   position: relative;
   display: flex;
   align-items: center;
   justify-content: center;
   border-radius: 10px;
   background-color: #FFF;
   width: 600px;
   height: 393px;
   flex-shrink: 0;
   flex-grow: 0;
}

@media (max-width: 768px) {
   .order-modal {
      width: 375px;
      max-width: 100%;
   }
}

.close-order-modal-btn--regular {
   display: flex;
   min-width: 120px;
   padding: 13px 24px;
   justify-content: center;
   align-items: center;
   border-radius: 4px;
   background: #FC6B40;
   border: none;
   color: #FFF;
   font-size: 13px;
   font-style: normal;
   font-weight: 700;
   line-height: 130%;
   cursor: pointer;
}

.close-order-modal-btn--cross {
   background: none;
   border: none;
   padding: 5px;
   cursor: pointer;
   position: absolute;
   top: 30px;
   right: 30px;
}

.order-modal-content {
   display: flex;
   flex-direction: column;
   justify-content: center;
   align-items: center;
}

.order-modal-content .icon-wrapper {
   display: flex;
   width: 85px;
   height: 85px;
   margin-bottom: 16px;
   justify-content: center;
   align-items: center;
   border-radius: 50%;
   background: #FFF0EC;
}

.order-modal-content .text-strong {
   margin-bottom: 7px;
   color: #3D445C;
   text-align: center;
   font-size: 18px;
   font-style: normal;
   font-weight: 400;
   line-height: 24px;
}

.order-modal-content .text-small {
   margin-bottom: 24px;
   color: #8B8F9D;
   text-align: center;
   font-size: 14px;
   font-style: normal;
   font-weight: 400;
   line-height: 130%;
}


/* Map modal CSS */

.map-modal-backdrop {
   position: fixed;
   inset: 0;
   background: rgba(0, 0, 0, 0.7);
   display: none;
   flex-direction: column;
   align-items: center;
   justify-content: center;
   z-index: 1000;
}

.map-modal {
   position: relative;
   display: flex;
   align-items: center;
   justify-content: center;
   flex-direction: column;
   border-radius: 10px;
   background-color: #FFF;
   width: 550px;
   min-height: 200px;
   flex-shrink: 0;
   flex-grow: 0;
   overflow: hidden;
}

.close-map-modal-btn--cross {
   display: flex;
   justify-content: center;
   align-items: center;
   width: 26px;
   height: 26px;
   border-radius: 50%;
   background-color: rgba(61, 68, 92, 0.32);
   border: none;
   cursor: pointer;
   position: absolute;
   top: 30px;
   right: 30px;
}

.map-modal-content {
   display: flex;
   flex-direction: column;
   justify-content: center;
   align-items: center;
}

.modal-map {
   display: block;
   width: 100%;
   height: 272px;
   object-fit: cover;
}

.map-modal-content-wrapper {
   width: 100%;
   padding: 20px;
}

.map-modal-content-wrapper .location-text {
   margin: 0 0 16px 0;
   color: #3D445C;
   font-size: 16px;
   font-style: normal;
   font-weight: 500;
   line-height: 130%;
   text-transform: capitalize;
}

.map-modal-price-checkbox-wrapper {
   display: flex;
   justify-content: space-between;
   align-items: flex-end;
}

.map-modal-price-wrapper {
   display: flex;
   flex-direction: column;
   gap: 4px;
}

.map-modal-price-wrapper .text {
   color: #8B8F9D;
   font-size: 13px;
   font-style: normal;
   font-weight: 400;
   line-height: 18px;
}

.map-modal-price-wrapper .price {
   color: #3D445C;
   font-size: 20px;
   font-style: normal;
   font-weight: 500;
   line-height: 32px;
}

.map-modal-checkbox {
   width: 96px !important;
   height: 43px !important;
}

/* Mobile table CSS */

@media (min-width: 769px) {
   .proposal-constructions-section .constructions-table-mobile {
      display: none;
   }
}

.constructions-table-mobile {
   position: relative;
   width: calc(100% + 32px);
   padding: 16px;
   color: #3D445C;
   font-size: 13px;
   font-style: normal;
   font-weight: 400;
   line-height: 18px;
   border-spacing: 0 8px;
   margin-left: -16px;
   margin-right: -16px;
   border-collapse: separate;
}

.constructions-table-mobile td {
   vertical-align: top;
}

.mobile-table-head {
   width: 35.7%;
}

.constructions-table-mobile .construction-photo-wrapper {
   width: 66px;
   height: 48px;
   cursor: pointer;
}

.constructions-table-mobile .construction-photo {
   object-fit: cover;
}

.constructions-table-mobile .show-on-map {
   display: flex;
   padding: 1px 5px 1px 0;
   justify-content: center;
   align-items: center;
   gap: 8px;
   border: none;
   background: none;
   cursor: pointer;
   color: #FC6B40;
   font-size: 13px;
   font-style: normal;
   font-weight: 400;
}

.va-center {
   margin-top: 7px;
}

.mobile-table-info .price {
   color: #3D445C;
   font-size: 13px;
   font-style: normal;
   font-weight: 700;
   line-height: 130%;
}

.constructions-table-mobile .select-button .to-choose,
.constructions-table-mobile .select-button .choosen {
   display: inline-flex;
   box-sizing: border-box;
   width: 100%;
   max-width: 375px;
}

.constructions-table-mobile .select-construction-label {
   margin: 0 auto;
}

.constructions-table-mobile .select-construction-label:has(.select-construction-checkbox:checked) .to-choose,
.constructions-table-mobile .select-button .choosen {
   display: none;
}

.constructions-table-mobile .select-construction-label:has(.select-construction-checkbox:checked) .choosen {
   display: inline-flex;
}


.constructions-table-mobile.checked,
.constructions-table tr.checked {
   background-color: #FFF0EC;
}


/* Mobile map modal CSS */

.map-modal-backdrop-mobile {
   position: fixed;
   inset: 0;
   background: rgba(0, 0, 0, 0.7);
   display: none;
   flex-direction: column;
   align-items: center;
   justify-content: center;
   z-index: 1000;
}

.map-modal-mobile {
   width: 100%;
   max-width: 450px;
   max-height: 100vh;
   overflow: scroll;
   padding-top: 16px;
   padding-bottom: 16px;
   background-color: #fff;
}

.construction-photo-wrapper-mobile {
   width: 66px;
}


.map-modal-mobile-container {
   padding-left: 16px;
   padding-right: 16px;
}

.map-modal-mobile-container .header-wrapper {
   display: flex;
   justify-content: space-between;
   align-items: center;
   padding-bottom: 16px;
   color: #3D445C;
   font-size: 18px;
   font-style: normal;
   font-weight: 400;
   line-height: 24px;
   border-bottom: 1px solid #E8EBF1;
}

.map-modal-mobile-container .header-wrapper button {
   background: none;
   border: none;
}

.map-modal-mobile-container .tabs-wrapper {
   display: flex;
   justify-content: flex-start;
   align-items: center;
   gap: 20px;
   padding-top: 24px;
   padding-bottom: 22px;
   color: #FC6B40;
   font-family: 'Helvetica Neue', Helvetica, 'helvetica', Arial, sans-serif;
   font-size: 14px;
   font-style: normal;
   font-weight: 500;
   line-height: 130%;
}

.tab-label:has(input[type="radio"]:checked) span {
   color: #3D445C;
}

.tab-label {
   position: relative;
}

.tab-label::after {
   display: none;
   content: "";
   position: absolute;
   left: 0;
   bottom: -22px;
   width: 100%;
   height: 2px;
   background-color: #FC6B40;
}

.tab-label:has(input[type="radio"]:checked)::after {
   display: block;
}

.mobile-modal-map {
   display: block;
   width: 100%;
   height: 297px;
   object-fit: cover;
   margin-bottom: 16px;
}

.map-modal-mobile-container .location-text {
   margin-bottom: 16px;
   color: #3D445C;
   font-size: 14px;
   font-style: normal;
   font-weight: 500;
   line-height: 130%;
}

.map-modal-mobile-info-table {
   border-collapse: separate;
   border-spacing: 0 8px;
   margin-bottom: 16px;
   text-align: left;
   vertical-align: middle;
   color: #3D445C;
   font-size: 13px;
   font-style: normal;
   font-weight: 400;
   line-height: 18px;
   width: 100%;
}

.map-modal-mobile-info-table tr {
   width: 100%;
   margin-bottom: 8px;
}

.map-modal-mobile-info-table .td-name {
   width: 39%;
}

.availability-calendar-wrapper {
   width: 100%;
   border-radius: 8px;
   margin-bottom: 16px;
   border: 1px solid #F2F5FB;
   overflow: hidden;
}

.calendar-title {
   margin-bottom: 12px;
   color: #3D445C;
   font-size: 13px;
   font-style: normal;
   font-weight: 500;
   line-height: 130%;
}

.availability-calendar-dates-wrapper {
   display: flex;
   justify-content: flex-start;
   align-items: flex-start;
   align-content: flex-start;
   flex-wrap: wrap;
   gap: 0
}

.availability-calendar-dates-wrapper span {
   display: flex;
   justify-content: center;
   align-items: center;
   color: #3D445C;
   font-size: 13px;
   font-style: normal;
   font-weight: 500;
   line-height: normal;
   width: 50px;
   height: 26px;
   flex-shrink: 0;
   background-color: #CDD4D9;
   border: none;
}

.availability-calendar-dates-wrapper span.booked {
   color: #FFF;
   background-color: #FC6B40;
}

.availability-calendar-dates-wrapper span.available {
   color: #FFF;
   background-color: #4FB14B;
}

.map-modal-mobile-price-wrapper {
   display: flex;
   flex-direction: column;
   justify-content: flex-start;
   align-items: flex-start;
   gap: 4px;
   margin-bottom: 23px;
   color: #8B8F9D;
   font-size: 13px;
   font-style: normal;
   font-weight: 400;
   line-height: 18px;
}

.map-modal-mobile-price-wrapper .price {
   color: #3D445C;
   font-size: 20px;
   font-style: normal;
   font-weight: 500;
   line-height: 32px;
}

.select-construction-label-mobile {
   display: block;
   width: 100%;
   max-width: 343px;
   margin: 0 auto;
}

.select-button-mobile .to-choose {
   width: 100%;
   display: flex;
   width: 343px;
   height: 43px;
   padding: 13px 24px;
   justify-content: center;
   align-items: center;
   gap: 10px;
   flex-shrink: 0;
   border-radius: 4px;
   background: #FC6B40;
   color: #FFF;
   font-size: 13px;
   font-style: normal;
   font-weight: 700;
   cursor: pointer;
}


.select-button-mobile .choosen {
   display: none;
   width: 100%;
   width: 343px;
   height: 43px;
   padding: 13px 24px;
   justify-content: center;
   align-items: center;
   font-style: normal;
   font-weight: 700;
   line-height: 130%;
   transition: all 0.1s ease-in-out;
   cursor: pointer;
   border-radius: 4px;
   border: 1px solid #CDD4D9;
   background-color: #FFF;
   font-size: 13px;
   color: #3D445C;
}

.select-construction-label-mobile:has(.select-construction-mobile-checkbox:checked) .to-choose {
   display: none;
}

.select-construction-label-mobile:has(.select-construction-mobile-checkbox:checked) .choosen {
   display: flex;
}
</style>


</body>






