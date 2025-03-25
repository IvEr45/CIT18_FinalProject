<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Planner</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            display: flex; 
            justify-content: center; 
            gap: 20px; 
        }
        #edit-title {
    width: 100%;
    font-size: 18px;
    padding: 5px;
}

#edit-ingredients, #edit-instructions {
    width: 100%;
    height: 200px;
    font-size: 16px;
    padding: 5px;
    resize: vertical; /* Allows resizing if needed */
}

        
        #recipe-list-box {
            width: 25%;
            border: 1px solid #ccc;
            padding: 10px;
            background: #f9f9f9;
            text-align: left;
            height: 300px;
            overflow-y: auto;
        }

        .recipe-item {
            padding: 10px;
            margin-bottom: 5px;
            background: #fff;
            border: 1px solid #ddd;
            cursor: pointer;
        }

        #chat-container { width: 50%; }
        #chat-box { width: 100%; border: 1px solid #ccc; padding: 10px; min-height: 200px; text-align: left; }
        
        .message { margin: 5px 0; padding: 5px; }
        .user { background: #cce5ff; text-align: right; }
        .ai { background: #e2e3e5; text-align: left; }
        
        #recipe-box {
            width: 30%; 
            border: 1px solid #ccc; 
            padding: 10px; 
            display: none; 
            text-align: left; 
            background: #f9f9f9;
        }
        #recipe-title { font-weight: bold; font-size: 18px; margin-bottom: 10px; }
        #save-btn { background: #28a745; color: white; border: none; padding: 5px 10px; cursor: pointer; margin-top: 10px; }
        #save-btn:hover { background: #218838; }
    </style>
</head>
<body>

    <div id="recipe-list-box">
        <h2>Saved Recipes</h2>
        <div id="recipe-list">Loading...</div>
    </div>

    <div id="chat-container">
        <h1>Recipe AI</h1>
        <div id="chat-box"></div>
        <input type="text" id="user-input" placeholder="Type your message..." />
        <button onclick="sendMessage()">Send</button>
    </div>

    <div id="recipe-box">
        <h2 id="recipe-title">Recipe</h2>
        <p id="recipe-content"></p>
        <button id="save-btn">Save Recipe</button>
    </div>

    <script>
    $(document).ready(function() {
        loadRecipes();
    });

    function loadRecipes() {
    $.get('/recipes', function(data) {
        let recipeList = $('#recipe-list');
        recipeList.empty();

        if (data.length === 0) {
            recipeList.append("<p>No recipes saved yet.</p>");
        } else {
            data.forEach(recipe => {
                recipeList.append(`
                    <div class="recipe-item">
                        <strong>${recipe.title}</strong>
                        <button class="edit-btn" onclick="editRecipe(${recipe.id}, \`${recipe.title}\`, \`${recipe.ingredients}\`, \`${recipe.instructions}\`)">✏️</button>
                        <button class="delete-btn" onclick="deleteRecipe(${recipe.id})">🗑️</button>
                        <div onclick="showRecipe(\`${recipe.title}\`, \`${recipe.ingredients}\`, \`${recipe.instructions}\`)">
                            <small>Click to view</small>
                        </div>
                    </div>
                `);
            });
        }
    });
}



    function sendMessage() {
        let userMessage = $('#user-input').val();
        if (!userMessage) return;

        $('#chat-box').append(`<div class="message user">${userMessage}</div>`);
        $('#user-input').val('');

        $.post('/chat', { message: userMessage, _token: '{{ csrf_token() }}' }, function(data) {
            let recipe = data.recipe;

            if (recipe.includes("Ingredients") && recipe.includes("Instructions")) {
                $('#recipe-box').show();
                $('#recipe-content').html(recipe.replace(/\n/g, "<br>"));

                let titleMatch = recipe.match(/^(.*?)[\n]/);
                let ingredientsMatch = recipe.match(/Ingredients:(.*?)Instructions:/s);
                let instructionsMatch = recipe.match(/Instructions:(.*)/s);

                let title = titleMatch ? titleMatch[1].trim() : "Untitled Recipe";
                let ingredients = ingredientsMatch ? ingredientsMatch[1].trim() : "";
                let instructions = instructionsMatch ? instructionsMatch[1].trim() : "";

                $('#save-btn').off('click').on('click', function () {
                    saveRecipe(title, ingredients, instructions);
                });
            } else {
                $('#chat-box').append(`<div class="message ai">${recipe}</div>`);
            }
        });
    }

    function saveRecipe(title, ingredients, instructions) {
        $.post('/save-recipe', {
            title: title,
            ingredients: ingredients,
            instructions: instructions,
            _token: '{{ csrf_token() }}'
        }, function(response) {
            alert(response.message);
            loadRecipes(); // Reload saved recipes after saving
        });
    }

    function showRecipe(title, ingredients, instructions) {
    $('#recipe-box').show();
    $('#recipe-title').text(title);
    $('#recipe-content').html(`
        <strong>Ingredients:</strong> <br> ${ingredients.replace(/\n/g, "<br>")} <br><br>
        <strong>Instructions:</strong> <br> ${instructions.replace(/\n/g, "<br>")}
    `);
}
    function deleteRecipe(id) {
    if (!confirm("Are you sure you want to delete this recipe?")) return;

    $.ajax({
        url: `/recipes/${id}`,
        type: 'DELETE',
        data: { _token: '{{ csrf_token() }}' }, 
        success: function(response) {
            alert(response.message);
            loadRecipes(); // Refresh the recipe list after deletion
        },
        error: function(xhr) {
            alert("Error: " + xhr.responseJSON.message);
        }
    });
}
function editRecipe(id, title, ingredients, instructions) {
    $('#recipe-box').show();
    $('#recipe-title').html(`<input type="text" id="edit-title" value="${title}" style="width: 100%; font-size: 18px; padding: 5px;" />`);
    $('#recipe-content').html(`
        <strong>Ingredients:</strong><br> 
        <textarea id="edit-ingredients" style="width: 100%; height: 150px; font-size: 16px; padding: 5px;">${ingredients}</textarea><br><br>
        <strong>Instructions:</strong><br> 
        <textarea id="edit-instructions" style="width: 100%; height: 200px; font-size: 16px; padding: 5px;">${instructions}</textarea><br><br>
        <button onclick="updateRecipe(${id})" id="update-btn" style="padding: 10px; font-size: 16px; cursor: pointer;">Save Changes</button>
    `);
}

function updateRecipe(id) {
    let updatedTitle = $('#edit-title').val();
    let updatedIngredients = $('#edit-ingredients').val();
    let updatedInstructions = $('#edit-instructions').val();

    $.ajax({
        url: `/recipes/${id}`,
        type: 'PUT',
        data: {
            title: updatedTitle,
            ingredients: updatedIngredients,
            instructions: updatedInstructions,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            alert(response.message);
            loadRecipes(); 
            $('#recipe-box').hide(); 
        },
        error: function(xhr) {
            alert("Error: " + xhr.responseJSON.message);
        }
    });
}



    </script>

</body>
</html>

