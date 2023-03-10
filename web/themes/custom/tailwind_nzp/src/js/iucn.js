(function ($, Drupal) { 
    $(document).ready(function(){

        const listItem = document.querySelectorAll('#iucn-list li');
        const selectedItem = document.querySelector("#iucn-list").getAttribute("data-designation").toLowerCase();
         
        listItem.forEach((ele) => {
          let badge = ele.children[0];
          let label = ele.children[1];
        
          if(label.textContent.toLowerCase() ===  selectedItem) {
            badge.classList.add("active");
          }
        });
    });
    
    
})(jQuery, Drupal);