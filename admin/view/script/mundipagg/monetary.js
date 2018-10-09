/**
 * @param inputObj jQuery object
 */
function changeInputValueToMonetary(inputObj) {
    setTimeout(function(){
        inputValue = inputObj.val();

        if(inputValue >= 999999999){
            inputValue = 999999999;
        }

        value = toFloat(inputValue);

        inputObj.val(value);
    }, 1800);
}

function toFloat(value) {
    amount = parseFloat(value);
    return amount.toFixed(2);
}