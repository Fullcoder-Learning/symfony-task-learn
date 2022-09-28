

function getTask(id, name, action){
    let taskModalTitle = document.getElementById('taskModalTitle');
    let taskButton = document.getElementById('taskButton');
    let taskDefinition = document.getElementById('taskDefinition');
    
    switch(action){
        case 'finish':
            taskModalTitle.textContent = 'Se va a finalizar la siguiente tarea';
            taskDefinition.textContent = id + " - " + name;
            taskButton.href = '/tasks/finish/' + id;
            taskButton.className  = 'btn btn-primary';
            break;
        case 'delete':
            taskModalTitle.textContent = 'Se va a eliminar la siguiente tarea';
            taskDefinition.textContent = id + " - " + name;
            taskButton.href = '/tasks/delete/' + id;
            taskButton.className  = 'btn btn-danger';
            break;
    }
}

// función para cargar datos en campos:
function editTask(id, name, description, dateCreated, isComplete, dateFinish){
    // recuperar nodos padres:
    let taskList = document.getElementById('taskList');
    let taskEdit = document.getElementById('taskEdit');
    let taskCreate = document.getElementById('taskCreate');

    // recuperar etiquetas a editar y añadir información:
    document.getElementById('taskForm').action = '/tasks/update/' + id;
    document.getElementById('task_name').value = name;
    document.getElementById('task_description').value = description;
    document.getElementById('taskDateCreated').textContent = dateCreated;
    document.getElementById('taskComplete').textContent = isComplete == true ? "Si" : "No"; 
    document.getElementById('taskDateFinish').textContent = isComplete == true ? dateFinish : "";

    // cambiar clases de boostrap para ocultar listado de tareas y mostrar editor:
    taskList.className = "d-none";
    taskEdit.className = "";
    taskCreate.className = "d-none";
}

// cancelar edición:
function cancelEdit(){
    let taskList = document.getElementById('taskList');
    let taskEdit = document.getElementById('taskEdit');
    let taskCreate = document.getElementById('taskCreate');

    // cambiar clases de boostrap para mostrar listado de tareas y ocultar editor:
    taskList.className = "text-start";
    taskEdit.className = "d-none";
    taskCreate.className = "row text-center align-items-end";
}