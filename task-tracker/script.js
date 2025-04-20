// DOM selection using querySelector
const taskForm = document.querySelector('#task-form');
const taskInput = document.querySelector('#task-input');
const taskList = document.querySelector('#task-list');
const clearBtn = document.querySelector('#clear-tasks');
const filterInput = document.querySelector('#filter');

// Load tasks from localStorage
document.addEventListener('DOMContentLoaded', loadTasksFromStorage);

// Add Task
taskForm.addEventListener('submit', function (e) {
  e.preventDefault();
  const taskText = taskInput.value.trim();
  if (taskText === '') return;

  addTaskToDOM(taskText);
  saveTaskToStorage(taskText);
  taskInput.value = '';
});

function addTaskToDOM(taskText) {
  const li = document.createElement('li');
  li.textContent = taskText;
  li.classList.add('task-item');

  // Add an attribute
  li.setAttribute('data-created', new Date().toISOString());

  // Create Delete Button
  const delBtn = document.createElement('button');
  delBtn.textContent = 'Delete';
  delBtn.className = 'delete-btn';

  li.appendChild(delBtn);

  // Use insertBefore to place new task on top
  if (taskList.firstChild) {
    taskList.insertBefore(li, taskList.firstChild);
  } else {
    taskList.appendChild(li);
  }

  // Style the element using JS
  li.style.cursor = 'pointer';
}

// Delete or complete task (event delegation)
taskList.addEventListener('click', function (e) {
  if (e.target.classList.contains('delete-btn')) {
    const li = e.target.parentElement;
    removeTaskFromStorage(li.textContent.replace('Delete', '').trim());
    li.remove();
  } else if (e.target.tagName === 'LI') {
    e.target.classList.toggle('completed');

    // Access custom attribute
    const createdAt = e.target.getAttribute('data-created');
    console.log(`Task was created at: ${createdAt}`);

    // Optional: remove attribute
    // e.target.removeAttribute('data-created');
  }
});

// Clear all tasks
clearBtn.addEventListener('click', function () {
  while (taskList.firstChild) {
    taskList.removeChild(taskList.firstChild); // Demonstrates removeChild()
  }
  localStorage.removeItem('tasks');
});

// Filter tasks
filterInput.addEventListener('keyup', function (e) {
  const text = e.target.value.toLowerCase();
  const items = taskList.querySelectorAll('li');
  items.forEach(function (item) {
    const taskText = item.firstChild.textContent.toLowerCase();
    item.style.display = taskText.includes(text) ? 'flex' : 'none';
  });
});

// ---- Local Storage Functions ---- //
function saveTaskToStorage(task) {
  let tasks = JSON.parse(localStorage.getItem('tasks')) || [];
  tasks.push(task);
  localStorage.setItem('tasks', JSON.stringify(tasks));
}

function loadTasksFromStorage() {
  let tasks = JSON.parse(localStorage.getItem('tasks')) || [];
  tasks.forEach(function (task) {
    addTaskToDOM(task);
  });
}

function removeTaskFromStorage(taskText) {
  let tasks = JSON.parse(localStorage.getItem('tasks')) || [];
  tasks = tasks.filter(task => task !== taskText);
  localStorage.setItem('tasks', JSON.stringify(tasks));
}
