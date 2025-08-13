
// Main JavaScript file
function init() {
    // Initialize the application
    var elements = document.querySelectorAll(".button");
    
    for (var i = 0; i < elements.length; i++) {
        elements[i].addEventListener("click", function(event) {
            event.preventDefault();
            console.log("Button clicked!");
        });
    }
}

// Helper functions
function addClass(element, className) {
    if (element.classList) {
        element.classList.add(className);
    } else {
        element.className += " " + className;
    }
}

window.onload = init;
