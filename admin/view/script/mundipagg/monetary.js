/**
 * @param inputObj jQuery object
 */
function toMonetary(inputObj) {
    setTimeout(function(){
        var inputValue = parseFloat(inputObj.val());
        if(inputValue >= 999999999){
            inputValue = 999999999;
        }
        inputObj.val(inputValue.toFixed(2));
    }, 2000);

}