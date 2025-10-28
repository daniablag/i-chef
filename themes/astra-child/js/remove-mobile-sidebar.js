document.addEventListener("DOMContentLoaded", function () {
  if (window.innerWidth < 768) {
    const videoBlock = document.getElementById("secondary");
    if (videoBlock) {
      videoBlock.remove();
    }
  }
});
