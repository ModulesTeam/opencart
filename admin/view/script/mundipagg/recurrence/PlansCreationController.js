var PlansCreationController = function (creationPageHandler)
{
    this.creationPageHandler = creationPageHandler;
};

PlansCreationController.prototype.init = function() {
    this.creationPageHandler.init(this);
};

PlansCreationController.prototype.addTemplateFromSelect = function() {
    var selectObj = this.creationPageHandler.getTemplateSelectElement();
    var selectedElementId = selectObj.val();

    console.log(selectedElementId);

};



var OpencartRecurrencyCreationPageHandler = function() {}

OpencartRecurrencyCreationPageHandler.prototype.init = function(plansCreationController) {
    //replace form action
    var formAction = $('#form-product').attr('action');
    var splitData = formAction.split("?");
    var baseUrl = splitData[0];
    splitData = splitData[1].split("&");
    splitData[0] = 'route=extension/payment/mundipagg/plans';
    var finalUrl = baseUrl + '?' + splitData.join("&") + "&action=save";
    $('#form-product').attr('action',finalUrl);

    //add handler for template add from select
    $('#add-template-from-select-button').on(
        'click',
        function() {
            plansCreationController.addTemplateFromSelect();
        }
    );
};

OpencartRecurrencyCreationPageHandler.prototype
.getTemplateSelectElement = function() {
    return $('#select-subscription');
};
