<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\web\View; // Import View

/** @var yii\web\View $this */
/** @var app\models\Category[] $categories */
/** @var app\models\Todo[] $todos */ // Adjust namespace if needed

// Generate URLs for JavaScript
$createUrl = Url::to(['site/create']);
$listUrl = Url::to(['site/list']);
$deleteUrl = Url::to(['site/delete', 'id' => '__id__']); // Placeholder for ID

?>

<div>
    <h4 class="text-center">To Do -list application</h4>
    
</div>

<div id="todo-form" class="mt-5">
<?php $form = ActiveForm::begin([
    'id' => 'todo-form-id',
    'enableAjaxValidation' => false,
    'action' => Url::to(['site/create']), // submit to site/create
]); ?>
<?= Html::dropDownList('category_id', null, \yii\helpers\ArrayHelper::map($categories, 'id', 'name'), ['id' => 'category-id']) ?>

<?= Html::textInput('name', '', ['id' => 'todo-name']) ?>

<?= Html::submitButton('Add', ['id' => 'submit-btn', 'class' => 'btn btn-success rounded']) ?>


<?php ActiveForm::end(); ?>

</div>
<br>
<hr>

<caption>Todo List</caption>
<!-- Corrected table tag -->
<table id="todo-table" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Category</th>
            <th>Todo Item</th>
            <th>Timestamp</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <!-- AJAX-loaded rows will appear here -->
    </tbody>
</table>


<?php
// Pass PHP variables (URLs) to JavaScript
$script = <<<JS
$(document).ready(function() {
    const CREATE_URL = '$createUrl';
    const LIST_URL = '$listUrl';
    const DELETE_URL_TEMPLATE = '$deleteUrl'; 

    // Function to load todos
    function loadTodos() {
        $.ajax({
            url: LIST_URL, // Use variable
            type: 'GET',
            dataType: 'json', // Expect JSON response
            success: function(data) {
                var tbody = $('#todo-table tbody');
                tbody.html(''); // Clear existing rows
                if (data && data.todos) {
                    $.each(data.todos, function(index, todo) {
                        
                        tbody.append('<tr data-id="' + todo.id + '">'
                            + '<td>' + (todo.category_name ? todo.category_name : 'N/A') + '</td>' // Handle null category
                            + '<td>' + todo.name + '</td>'
                            + '<td>' + todo.timestamp + '</td>' 
                            + '<td><button class="btn btn-danger btn-sm delete-btn">Delete</button></td>'
                            + '</tr>');
                    });
                } else {
                     tbody.append('<tr><td colspan="4">No todos found.</td></tr>');
                     console.error("Received invalid data structure:", data);
                }
            },
            error: function(xhr) {
                console.error("Error loading todos:", xhr.responseText);
                 $('#todo-table tbody').html('<tr><td colspan="4">Error loading data.</td></tr>');
            }
        });
    }

    // Handle form submission
    $('#todo-form-id').on('submit', function(e) {
        e.preventDefault(); 

        var todoName = $('#todo-name').val().trim();
        var categoryId = $('#category-id').val();

        if (!todoName) {
            alert('Please enter a todo item name.');
            return;
        }
         if (!categoryId) {
            alert('Please select a category.');
            return;
        }


        $.ajax({
            url: $(this).attr('action'), 
            type: 'POST',
            data: {
                name: todoName,
                category_id: categoryId,
                _csrf: yii.getCsrfToken() 
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#todo-name').val('');
                    
                    loadTodos(); 
                } else {
                    // Handle validation errors if returned from server
                    alert('Error adding todo: ' + (data.message || 'Unknown error'));
                    console.error("Add error:", data.errors || data);
                }
            },
            error: function(xhr) {
                alert('Error submitting form. Please check console.');
                console.error("Submit error:", xhr.responseText);
            }
        });
    });

    
    $('#todo-table tbody').on('click', '.delete-btn', function() {
        var id = $(this).closest('tr').data('id');
        var deleteUrl = DELETE_URL_TEMPLATE.replace('__id__', id); /

       
        var confirmPromise = typeof Swal !== 'undefined'
            ? Swal.fire({
                  title: 'Are you sure?',
                  text: "You won't be able to revert this!",
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonColor: '#3085d6',
                  cancelButtonColor: '#d33',
                  confirmButtonText: 'Yes, delete it!'
              })
            : Promise.resolve({ isConfirmed: confirm('Are you sure you want to delete this todo?') });

        confirmPromise.then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: deleteUrl, 
                    type: 'POST',
                    data: {
                        _csrf: yii.getCsrfToken()
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            loadTodos(); 
                            if (typeof Swal !== 'undefined') {
                                Swal.fire('Deleted!', 'Your todo has been deleted.', 'success');
                            } else {
                                alert('Todo deleted successfully.');
                            }
                        } else {
                            alert('Error deleting todo: ' + (data.message || 'Unknown error'));
                             console.error("Delete error:", data);
                        }
                    },
                    error: function(xhr) {
                         alert('Error deleting todo. Please check console.');
                        console.error("Delete error:", xhr.responseText);
                    }
                });
            }
        });
    });

    loadTodos();
});
JS;


$this->registerJs($script, View::POS_READY); 

\yii\web\YiiAsset::register($this);

?>