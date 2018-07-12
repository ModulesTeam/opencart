$(document).ready(function () {
    getModalContent();
    showHidePartialAmountInput();
    chargeActionSubmit();
});

function showHidePartialAmountInput(){
    $(".actionInputs").on("click", function () {
        if(
            $(this).val() == "partial_capture" ||
            $(this).val() == "partial_cancel"
        ){
            $(".chargeAmount").show();
        }else{
            $(".chargeAmount").hide();
        }
    })
}

function getModalContent() {
    $(".callActionModal").on("click", function () {
        $(".modal-content").hide();
        $(".loader").show();
        $(".info").html("").val("");
        $("#actionButton").attr("disabled");

        var chargeId = $(this).val();
        var orderId = $(this).attr("order-id")
        var url = $("#chargeModalInformationUrl").val();
        var action = $(this).attr("action-type");

        sendData(chargeId, action, orderId, url, fillModalInformation, "");
    });
}

function sendData(chargeId, action, orderId, url, callback, postData) {
    var data = "";
    $.post(
        url,
        {
            charge_id: chargeId,
            order_id: orderId,
            action: action,
            postData: postData
        }
    ).success(function(data) {
        var data = jQuery.parseJSON(data);

        console.log(data);
        if(data.charge_id){
            callback(action, data);
            $(".loader").hide();
            $(".modal-content").show();
            $("#actionButton").removeAttr("disabled");
            return;
        }else{
            alert('Não foi possível carregar as informações dessa cobrança');
            $('#orderActionsModal').modal('hide');
        }
    }).fail(function() {
        alert('Não foi possível carregar as informações dessa cobrança');
        $('#orderActionsModal').modal('hide');
    });
}

function fillModalInformation(action, chargeData) {
    var chargeActionText = chargeData.text.charge_action.replace("%s", action);
    var totalActionText =
        chargeData.text.
        total_action.
        replace("%s", action);
    var partialActionText =
        chargeData.text.
        partial_action.
        replace("%s", action);
    var howDoYouWantText =
        chargeData.text.
        how_do_you_want.
        replace("%s", action);

    $("#modalTitle").text(chargeActionText);
    $("#actionButton").text(chargeActionText).val(action);
    $("#action_total").val("total_" + action);
    $("#action_partial").val("partial_" + action);
    $("#orderActionsModal #chargePaymentMethod").text(chargeData.payment_method);
    $("#orderActionsModal #action").text(action);
    $("#orderActionsModal #totalActionText").text(totalActionText);
    $("#orderActionsModal #partialActionText").text(partialActionText);
    $("#orderActionsModal #howDoYouWant").text(howDoYouWantText);
    $("#orderActionsModal #currencySymbol").text(chargeData.currency_symbol);
    $("#orderActionsModal #chargeId").text(chargeData.charge_id);
    $("#orderActionsModal #orderId").val(chargeData.order_id);
    $("#orderActionsModal #chargeAmount").text(
        chargeData.currency_symbol +
        chargeData.formatted_amount
    );
    $("#orderActionsModal #selectedAmount").
        val(chargeData.formatted_amount).
        attr("max", chargeData.formatted_amount);
}

function fillModalMessage() {

}

function chargeActionSubmit() {
   $("#actionButton").on("click", function () {
       $(".loader").show();
       var chargeId = $("#chargeId").text();
       var orderId = $("#orderId").val();
       var url = $("#performChargeActionUrl").val();
       var selectedAmount = $("#selectedAmount").val();
       var action = "";

       $(".actionInputs").each(function () {
           if($(this).is(':checked')){
            action = $(this).val();
           }
       })
           console.log(action);
       sendData(chargeId, action, orderId, url, fillModalMessage, selectedAmount);
   })
}

