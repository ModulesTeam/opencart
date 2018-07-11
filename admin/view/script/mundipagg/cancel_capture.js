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
        chargeId = $(this).val();
        url = $("#chargeModalInformationUrl").val();

        $.post(
            url,
            {
                charge_id: chargeId
            }
        ).done(function(data) {
            console.log(data);
            if(data){
                $(".modal-content").show();
                $(".loader").hide();
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