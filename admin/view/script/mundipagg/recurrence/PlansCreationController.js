var PlansCreationController = function (formModelClass)
{
    this.creationPageFormModel = new formModelClass(this);
    this.templateSnapshop = null;
};

PlansCreationController.prototype.init = function() {
    this.creationPageFormModel.init();
};

PlansCreationController.prototype.addTemplateFromSelect = function() {
    var templateInfoUrl = this.creationPageFormModel.getTemplateInfoUrl();
    var selectObj = this.creationPageFormModel.getTemplateSelectElement();
    var selectedElementId = selectObj.val();

    this.creationPageFormModel
        .getAddTemplateFromSelectButtonElement()
        .prop( "disabled", true )
        .find("i")
        .addClass('fa-cog fa-spin')
        .removeClass('fa-plus-circle');

    $.ajax({
        url: templateInfoUrl + "&template_id=" + selectedElementId,
        success: function(result) {
            this.templateSnapshop = result;
            this.creationPageFormModel
                .getTemplateSnapshotDetailPanelElement()
                .show();
            this.creationPageFormModel
                .getAddTemplateFromSelectButtonElement()
                .hide();
            this.creationPageFormModel
                .getTemplateSelectEditPanelElement()
                .hide();
        }.bind(this),
        error: function(result) {
            console.log("error",result.responseJSON);
        },
        complete: function() {
            this.creationPageFormModel
                .getAddTemplateFromSelectButtonElement()
                .prop( "disabled", false)
                .find("i")
                .addClass('fa-plus-circle')
                .removeClass('fa-cog fa-spin')
        }.bind(this)
    });
};

var OpencartRecurrencyCreationFormModel = function(formController) {
  this.formController = formController;
};

OpencartRecurrencyCreationFormModel.prototype.init = function() {
    //replace form action
    var formAction = $('#form-product').attr('action');
    var splitData = formAction.split("?");
    var baseUrl = splitData[0];
    splitData = splitData[1].split("&");
    splitData[0] = 'route=extension/payment/mundipagg/plans';
    var finalUrl = baseUrl + '?' + splitData.join("&") + "&action=save";
    $('#form-product').attr('action',finalUrl);

    //defining templateInfoUrl
    splitData[0] = 'route=extension/payment/mundipagg/templates';
    this.templateInfoUrl = baseUrl + '?' + splitData.join("&") + "&action=info";

    //add handler for template add from select
    this.getAddTemplateFromSelectButtonElement().on(
        'click',
        function() {
            this.formController.addTemplateFromSelect();
        }.bind(this)
    );

    //add handler for template snapshop remove button
    $('#template-snapshot-remove').on(
        'click',
        function() {
            this.formController.templateSnapshop = null;
            this.getTemplateSnapshotDetailPanelElement().hide();
            this.getAddTemplateFromSelectButtonElement().show();
            this.getTemplateSelectEditPanelElement().show();
        }.bind(this)
    );
};

OpencartRecurrencyCreationFormModel.prototype
.getTemplateSelectElement = function() {
    return $('#select-subscription');
};

OpencartRecurrencyCreationFormModel.prototype
    .getTemplateSnapshotDetailPanelElement = function() {
    return $('#template-snapshot-detail-panel');
};
OpencartRecurrencyCreationFormModel.prototype
    .getAddTemplateFromSelectButtonElement = function() {
    return $('#add-template-from-select-button');
};
OpencartRecurrencyCreationFormModel.prototype
    .getTemplateSelectEditPanelElement = function() {
    return $('#template-select-edit-panel');
};

OpencartRecurrencyCreationFormModel.prototype
    .getTemplateInfoUrl = function() {
    return this.templateInfoUrl;
};


