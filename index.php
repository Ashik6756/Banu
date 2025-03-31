<?php
// Створюємо папку violators, якщо її немає
if (!file_exists('violators')) {
    mkdir('violators', 0777, true);
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ЄБали - Архів порушників</title>
    <style>
        /* Тут ваш CSS з попереднього коду */
        :root {
            --primary-color: #4a148c;
            --secondary-color: #7b1fa2;
            --accent-color: #e91e63;
            --light-color: #f3e5f5;
            --dark-color: #12005e;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* ... (весь інший CSS залишається без змін) ... */
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <h1>ЄБали</h1>
        <p>Архів порушників за датами</p>
    </header>
    
    <div class="container">
        <section class="upload-section fade-in">
            <h2><i class="fas fa-upload"></i> Додати нового порушника</h2>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="violatorName">Ім'я порушника</label>
                    <input type="text" id="violatorName" class="form-control" placeholder="Введіть ім'я або опис порушника" required>
                </div>
                
                <div class="form-group">
                    <label for="violatorDate">Дата порушення</label>
                    <input type="date" id="violatorDate" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Фото порушника</label>
                    <input type="file" id="violatorPhotos" class="file-input" multiple accept="image/*" name="photos[]">
                    <label for="violatorPhotos" class="file-label">
                        <i class="fas fa-images"></i>
                        <span>Перетягніть фото сюди або клацніть для вибору</span>
                    </label>
                    <div class="preview-container" id="previewContainer"></div>
                </div>
                
                <button type="submit" class="btn"><i class="fas fa-save"></i> Зберегти</button>
            </form>
        </section>
        
        <section class="archive-section fade-in">
            <h2><i class="fas fa-archive"></i> Архів порушників</h2>
            <div class="date-folders" id="dateFolders">
                <div class="no-data">
                    <i class="fas fa-folder-open" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                    <p>Завантаження даних...</p>
                </div>
            </div>
        </section>
    </div>
    
    <footer>
        <p>ЄБали &copy; <?php echo date('Y'); ?>. Всі права захищені.</p>
    </footer>
    
    <!-- Модальне вікно для перегляду порушника -->
    <div class="modal" id="violatorModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Деталі порушника</h3>
                <button class="close-btn" id="closeModal">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Тут будуть відображатися деталі порушника -->
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Елементи форми
            const uploadForm = document.getElementById('uploadForm');
            const violatorNameInput = document.getElementById('violatorName');
            const violatorDateInput = document.getElementById('violatorDate');
            const violatorPhotosInput = document.getElementById('violatorPhotos');
            const previewContainer = document.getElementById('previewContainer');
            const dateFolders = document.getElementById('dateFolders');
            
            // Елементи модального вікна
            const violatorModal = document.getElementById('violatorModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            const closeModalBtn = document.getElementById('closeModal');
            
            // Завантажити дані при завантаженні сторінки
            loadArchiveData();
            
            // Відображення попереднього перегляду фото
            violatorPhotosInput.addEventListener('change', function(e) {
                previewContainer.innerHTML = '';
                
                for (let file of e.target.files) {
                    if (!file.type.startsWith('image/')) continue;
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'remove-btn';
                        removeBtn.innerHTML = '&times;';
                        removeBtn.addEventListener('click', function() {
                            previewItem.remove();
                            // Оновити список файлів у input
                            updateFileInput();
                        });
                        
                        previewItem.appendChild(img);
                        previewItem.appendChild(removeBtn);
                        previewContainer.appendChild(previewItem);
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Оновити список файлів у input після видалення
            function updateFileInput() {
                const dataTransfer = new DataTransfer();
                const previewItems = previewContainer.querySelectorAll('.preview-item');
                
                Array.from(violatorPhotosInput.files).forEach(file => {
                    let fileExists = false;
                    previewItems.forEach(item => {
                        if (item.querySelector('img').src.includes(file.name)) {
                            fileExists = true;
                        }
                    });
                    if (fileExists) {
                        dataTransfer.items.add(file);
                    }
                });
                
                violatorPhotosInput.files = dataTransfer.files;
            }
            
            // Обробка відправлення форми
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const name = violatorNameInput.value.trim();
                const date = violatorDateInput.value;
                const files = violatorPhotosInput.files;
                
                if (!name || !date || files.length === 0) {
                    alert('Будь ласка, заповніть всі поля та додайте хоча б одне фото');
                    return;
                }
                
                // Створюємо FormData для відправки
                const formData = new FormData();
                formData.append('name', name);
                formData.append('date', date);
                
                for (let i = 0; i < files.length; i++) {
                    formData.append('photos[]', files[i]);
                }
                
                // Відправляємо дані на сервер
                fetch('save.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Порушника успішно додано до архіву!');
                        uploadForm.reset();
                        previewContainer.innerHTML = '';
                        loadArchiveData();
                    } else {
                        alert('Помилка: ' + (data.message || 'Не вдалося зберегти дані'));
                    }
                })
                .catch(error => {
                    console.error('Помилка:', error);
                    alert('Сталася помилка при збереженні даних');
                });
            });
            
            // Завантажити дані архіву з сервера
            function loadArchiveData() {
                fetch('load.php')
                    .then(response => response.json())
                    .then(data => {
                        renderArchive(data);
                    })
                    .catch(error => {
                        console.error('Помилка завантаження даних:', error);
                        dateFolders.innerHTML = `
                            <div class="no-data">
                                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                                <p>Не вдалося завантажити дані</p>
                            </div>
                        `;
                    });
            }
            
            // Функція для відображення архіву
            function renderArchive(data) {
                if (!data || Object.keys(data).length === 0) {
                    dateFolders.innerHTML = `
                        <div class="no-data">
                            <i class="fas fa-folder-open" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                            <p>Ще немає доданих порушників</p>
                        </div>
                    `;
                    return;
                }
                
                dateFolders.innerHTML = '';
                
                // Сортування дат у зворотньому порядку (від нових до старих)
                const sortedDates = Object.keys(data).sort((a, b) => new Date(b) - new Date(a));
                
                for (const date of sortedDates) {
                    const violators = data[date];
                    
                    const folder = document.createElement('div');
                    folder.className = 'folder fade-in';
                    
                    const folderHeader = document.createElement('div');
                    folderHeader.className = 'folder-header';
                    
                    const formattedDate = formatDate(date);
                    folderHeader.textContent = formattedDate;
                    
                    const folderContent = document.createElement('div');
                    folderContent.className = 'folder-content';
                    
                    const violatorList = document.createElement('ul');
                    violatorList.className = 'violator-list';
                    
                    for (const violator of violators) {
                        const violatorItem = document.createElement('li');
                        violatorItem.className = 'violator-item';
                        
                        const violatorThumb = document.createElement('div');
                        violatorThumb.className = 'violator-thumb';
                        
                        const thumbImg = document.createElement('img');
                        thumbImg.src = 'violators/' + date + '/' + violator.folder + '/' + violator.images[0];
                        thumbImg.alt = violator.name;
                        
                        violatorThumb.appendChild(thumbImg);
                        
                        const violatorName = document.createElement('div');
                        violatorName.className = 'violator-name';
                        violatorName.textContent = violator.name;
                        
                        const viewBtn = document.createElement('a');
                        viewBtn.className = 'view-btn';
                        viewBtn.href = '#';
                        viewBtn.innerHTML = '<i class="fas fa-eye"></i> Переглянути';
                        
                        viewBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            showViolatorDetails(violator, formattedDate, date);
                        });
                        
                        violatorItem.appendChild(violatorThumb);
                        violatorItem.appendChild(violatorName);
                        violatorItem.appendChild(viewBtn);
                        violatorList.appendChild(violatorItem);
                    }
                    
                    folderContent.appendChild(violatorList);
                    folder.appendChild(folderHeader);
                    folder.appendChild(folderContent);
                    dateFolders.appendChild(folder);
                }
            }
            
            // Функція для форматування дати
            function formatDate(dateString) {
                const options = { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' };
                const date = new Date(dateString);
                return date.toLocaleDateString('uk-UA', options);
            }
            
            // Функція для відображення деталей порушника у модальному вікні
            function showViolatorDetails(violator, formattedDate, date) {
                modalTitle.textContent = `${violator.name} - ${formattedDate}`;
                
                let imagesHTML = '';
                for (const image of violator.images) {
                    imagesHTML += `
                        <div class="violator-image">
                            <img src="violators/${date}/${violator.folder}/${image}" alt="${violator.name}">
                        </div>
                    `;
                }
                
                modalBody.innerHTML = `
                    <div class="violator-details">
                        <div>
                            <h4>Ім'я порушника:</h4>
                            <p>${violator.name}</p>
                        </div>
                        <div>
                            <h4>Дата порушення:</h4>
                            <p>${formattedDate}</p>
                        </div>
                        <div>
                            <h4>Фото порушника:</h4>
                            <div class="violator-images">
                                ${imagesHTML}
                            </div>
                        </div>
                    </div>
                `;
                
                violatorModal.style.display = 'flex';
            }
            
            // Закриття модального вікна
            closeModalBtn.addEventListener('click', function() {
                violatorModal.style.display = 'none';
            });
            
            // Закриття модального вікна при кліку на затемнений фон
            violatorModal.addEventListener('click', function(e) {
                if (e.target === violatorModal) {
                    violatorModal.style.display = 'none';
                }
            });
            
            // Встановлення сьогоднішньої дати за замовчуванням
            const today = new Date().toISOString().split('T')[0];
            violatorDateInput.value = today;
        });
    </script>
</body>
</html>