/**
 * @param inputObj jQuery object
 */
function toMonetary(inputObj) {
    var inputValue = parseFloat(inputObj.val());
    if(inputValue >= 999999999){
        inputValue = 999999999;
    }
    inputObj.val(inputValue.toFixed(2));
}