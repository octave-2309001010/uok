// Select elements
const taskForm = document.getElementById('task-form');
const taskInput = document.getElementById('task-input');
const taskList = document.getElementById('task-list');
const clearBtn = document.getElementById('clear-tasks');
const filterInput = document.getElementById('filter');

// Add task
taskForm.addEventListener('submit', function (e) {
  e.preventDefault();
  const taskText = taskInput.value.trim();
  if (taskText === '') return;

  const li = document.createElement('li');
  li.textContent = taskText;

  const delBtn = document.createElement('button');
  delBtn.textContent = 'Delete';
  delBtn.className = 'delete-btn';
  li.appendChild(delBtn);

  taskList.appendChild(li);
  taskInput.value = '';
});

// Delete or complete task using event delegation
taskList.addEventListener('click', function (e) {
  if (e.target.classList.contains('delete-btn')) {
    e.target.parentElement.remove();
  } else if (e.target.tagName === 'LI') {
    e.target.classList.toggle('completed');
  }
});

// Clear all tasks
clearBtn.addEventListener('click', function () {
  taskList.innerHTML = '';
});

// Filter tasks
filterInput.addEventListener('keyup', function (e) {
  const text = e.target.value.toLowerCase();
  const items = taskList.getElementsByTagName('li');
  Array.from(items).forEach(function (item) {
    const itemText = item.firstChild.textContent.toLowerCase();
    item.style.display = itemText.includes(text) ? 'flex' : 'none';
  });
});
