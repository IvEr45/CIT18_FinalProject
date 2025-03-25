<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Planner</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        #recipe-list-box, #chat-container, #recipe-box {
            border: 1px solid #ccc;
            padding: 10px;
            background: #f9f9f9;
            text-align: left;
            height: 400px;
            overflow-y: auto;
        }
        #recipe-list-box { width: 25%; }
        #chat-container { width: 50%; }
        #recipe-box { width: 30%; }
        .recipe-item {
            padding: 10px;
            margin-bottom: 5px;
            background: #fff;
            border: 1px solid #ddd;
            cursor: pointer;
        }
        .recipe-buttons button {
            margin-top: 10px;
            padding: 5px;
            cursor: pointer;
        }
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
        <p id="recipe-content">Select a recipe to view details.</p>
        <div class="recipe-buttons">
            <button id="save-btn">Save</button>
            <button id="edit-btn">Edit</button>
            <button id="delete-btn">Delete</button>
        </div>
    </div>

    <script>
    $(document).ready(function () {
        loadRecipes();
    });

    function loadRecipes() {
        $.get('/recipes', function (data) {
            let recipeList = $('#recipe-list');
            recipeList.empty();

            if (data.length === 0) {
                recipeList.append("<p>No recipes saved yet.</p>");
            } else {
                data.forEach(recipe => {
                    recipeList.append(`
                        <div class="recipe-item" data-id="${recipe.id}" data-title="${recipe.title}" 
                            data-ingredients="${recipe.ingredients}" data-instructions="${recipe.instructions}">
                            <strong>${recipe.title}</strong>
                        </div>
                    `);
                });
            }
        });
    }

    $(document).on('click', '.recipe-item', function () {
        let id = $(this).data('id');
        let title = $(this).data('title');
        let ingredients = $(this).data('ingredients');
        let instructions = $(this).data('instructions');

        displayRecipe(id, title, ingredients, instructions);
    });

    function sendMessage() {
        let userMessage = $('#user-input').val();
        if (!userMessage) return;

        $('#chat-box').append(`<div class="message user">${userMessage}</div>`);
        $('#user-input').val('');

        $.post('/chat', { message: userMessage, _token: '{{ csrf_token() }}' }, function(data) {
            let recipe = data.recipe;

            if (recipe.includes("Ingredients") && recipe.includes("Instructions")) {
                let titleMatch = recipe.match(/^(.*?)[\n]/);
                let ingredientsMatch = recipe.match(/Ingredients:(.*?)Instructions:/s);
                let instructionsMatch = recipe.match(/Instructions:(.*)/s);

                let title = titleMatch ? titleMatch[1].trim() : "Untitled Recipe";
                let ingredients = ingredientsMatch ? ingredientsMatch[1].trim() : "";
                let instructions = instructionsMatch ? instructionsMatch[1].trim() : "";

                displayRecipe(null, title, ingredients, instructions);
            } else {
                $('#chat-box').append(`<div class="message ai">${recipe}</div>`);
            }
        });
    }

    function displayRecipe(id, title, ingredients, instructions) {
        $('#recipe-title').text(title);
        $('#recipe-content').html(`
            <strong>Ingredients:</strong> <br> ${ingredients.replace(/\n/g, "<br>")} <br><br>
            <strong>Instructions:</strong> <br> ${instructions.replace(/\n/g, "<br>")}
        `);

        $('.recipe-buttons').html(`
            <button id="save-btn">Save</button>
            ${id ? `<button id="edit-btn" data-id="${id}">Edit</button>` : ''}
            ${id ? `<button id="delete-btn" data-id="${id}">Delete</button>` : ''}
        `);
    }

    $(document).on('click', '#save-btn', function () {
        let title = $('#recipe-title').text();
        let ingredients = $('#recipe-content').html().replace(/<br>/g, "\n");
        let instructions = $('#recipe-content').html().replace(/<br>/g, "\n");

        $.post('/save-recipe', { title, ingredients, instructions, _token: '{{ csrf_token() }}' }, function (response) {
            alert(response.message);
            loadRecipes();
        });
    });

    $(document).on('click', '#edit-btn', function () {
        let id = $(this).data('id');
        let title = $('#recipe-title').text();
        let ingredients = $('#recipe-content').text();
        let instructions = $('#recipe-content').text();

        $('#recipe-title').html(`<input type="text" id="edit-title" value="${title}" />`);
        $('#recipe-content').html(`
            <strong>Ingredients:</strong><br> 
            <textarea id="edit-ingredients">${ingredients}</textarea><br><br>
            <strong>Instructions:</strong><br> 
            <textarea id="edit-instructions">${instructions}</textarea><br><br>
        `);

        $('.recipe-buttons').html(`<button id="update-btn" data-id="${id}">Save Changes</button>`);
    });

    $(document).on('click', '#update-btn', function () {
        let id = $(this).data('id');
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
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                alert(response.message);
                loadRecipes();
                displayRecipe(id, updatedTitle, updatedIngredients, updatedInstructions);
            },
            error: function (xhr) {
                alert("Error: " + xhr.responseJSON.message);
            }
        });
    });

    $(document).on('click', '#delete-btn', function () {
        let id = $(this).data('id');
        if (!confirm("Are you sure you want to delete this recipe?")) return;

        $.ajax({
            url: `/recipes/${id}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function (response) {
                alert(response.message);
                loadRecipes();
                $('#recipe-title').text("Recipe");
                $('#recipe-content').html("Select a recipe to view details.");
                $('.recipe-buttons').html('');
            }
        });
    });
    </script>
</body>
</html>
