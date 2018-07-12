$(document).ready(function () {
    getModalContent();
    showHidePartialAmountInput();

    /*$('.moedaReal').inputmask('decimal', {
        radixPoint:",",
        groupSeparator: ".",
        autoGroup: true,
        digits: 2,
        digitsOptional: false,
        placeholder: '0',
        rightAlign: false,
        onBeforeMask: function (value, opts) {
            return value;
        }
    });*/
});

function showHidePartialAmountInput(){
    $(".actionInputs").on("click", function () {
        if($(this).val() == "partial"){
            $(".chargeAmount").show();
        }else{
            $(".chargeAmount").hide();
        }
    })
}

function getModalContent() {
    $(".callActionModal").on("click", function(){
        $(".modal-content").hide();
        $(".loader").show();
        $(".info").html("").val("");

        var chargeData = "";
        var chargeId = $(this).val();
        var orderId = $(this).attr("order-id")
        var url = $("#chargeModalInformationUrl").val();
        var action = $(this).attr("action-type");

        $.post(
            url,
            {
                charge_id: chargeId,
                order_id: orderId
            }
        ).success(function(data) {
            var chargeData = jQuery.parseJSON(data);

            console.log(chargeData);
            if(chargeData.charge_id){
                fillModalInformation(action, chargeData);
                $(".loader").hide();
                $(".modal-content").show();
                return;
            }else{
                alert('Não foi possível carregar as informações dessa cobrança');
                $('#orderActionsModal').modal('hide');
            }
        }).fail(function() {
            alert('Não foi possível carregar as informações dessa cobrança');
            $('#orderActionsModal').modal('hide');
        });
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

    $("#orderActionsModal #selectedAmount").val(chargeData.formatted_amount);
    $("#modalTitle").text(chargeActionText);
    $("#actionButton").text(chargeActionText);
    $("#orderActionsModal #chargePaymentMethod").text(chargeData.payment_method);
    $("#orderActionsModal #action").text(action);
    $("#orderActionsModal #totalActionText").text(totalActionText);
    $("#orderActionsModal #partialActionText").text(partialActionText);
    $("#orderActionsModal #howDoYouWant").text(howDoYouWantText);
    $("#orderActionsModal #currencySymbol").text(chargeData.currency_symbol);
    $("#orderActionsModal #chargeId").text(chargeData.charge_id);
    $("#orderActionsModal #chargeAmount").text(
        chargeData.currency_symbol +
        chargeData.formatted_amount
    );

}
