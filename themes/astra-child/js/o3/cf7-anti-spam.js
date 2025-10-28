document.addEventListener("DOMContentLoaded", function() {
    var elements = document.getElementsByClassName("agree");
    if (elements && elements.length) {
        for (var i = 0; i < elements.length; i++) {
            elements[i].checked = false;
        }
    }
});
