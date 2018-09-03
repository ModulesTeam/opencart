var PlansCreationController = function (mundipaggRoot,formModelClass)
{
    this.mundipaggRoot = mundipaggRoot;
    this.creationPageFormModel = new formModelClass(this);
    this.templateSnapshot = null;
};

PlansCreationController.prototype.init = function() {
    this.creationPageFormModel.init();
    if (typeof MundipaggTemplateSnapshot !== "undefined") {
        this.showConfigTable(MundipaggTemplateSnapshot);
    }
    if (typeof MundipaggRecurrenceProducts !== "undefined") {
        MundipaggRecurrenceProducts.id.forEach(function(value,index){
            this.creationPageFormModel.addProductToPlan({
                id: value,
                name: MundipaggRecurrenceProducts.name[index],
                cycleType: MundipaggRecurrenceProducts.cycleType[index],
                cycles: MundipaggRecurrenceProducts.cycles[index],
                quantity: MundipaggRecurrenceProducts.quantity[index],
                thumb: MundipaggRecurrenceProducts.thumb[index]
            });
        }.bind(this));
    }
};

PlansCreationController.prototype.removeTemplateSnapShotDataFromForm = function() {
    this.creationPageFormModel.getFormElement()
        .find('#mundipagg-template-snapshot-data').remove();
};
PlansCreationController.prototype.addTemplateSnapShotDataToForm = function() {
    var data = btoa(JSON.stringify(this.templateSnapshot));
    this.creationPageFormModel.getFormElement()
        .append('' +
            '<input type="hidden" id="mundipagg-template-snapshot-data" ' +
            'name="mundipagg-template-snapshot-data" value="' + data + '" />');
};

PlansCreationController.prototype.showConfigTable = function(templateSnapshot) {
    this.templateSnapshot = templateSnapshot;

    this.creationPageFormModel
        .updateTemplateSnapshotDetailPanel(
            this.templateSnapshot
        );

    this.creationPageFormModel
        .getTemplateSnapshotDetailPanelElement()
        .show();
    this.creationPageFormModel
        .getAddTemplateFromSelectButtonElement()
        .hide();
    this.creationPageFormModel
        .getTemplateSelectEditPanelElement()
        .hide();

    this.addTemplateSnapShotDataToForm();
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

    this.creationPageFormModel
        .getTemplateSelectElement()
        .prop( "disabled", true );

    this.removeTemplateSnapShotDataFromForm();
    $.ajax({
        url: templateInfoUrl + "&template_id=" + selectedElementId,
        success: this.showConfigTable.bind(this),
        error: function(result) {
            console.log("error", result);
        },
        complete: function() {
            removeLoadingAnimation(this);
        }.bind(this)
    });
};

/**
 * Stop loading and enable template select
 * @param $obj
 */
function removeLoadingAnimation($obj) {
    $obj.creationPageFormModel
        .getAddTemplateFromSelectButtonElement()
        .prop("disabled", false)
        .find("i")
        .addClass('fa-plus-circle')
        .removeClass('fa-cog fa-spin');

    $obj.creationPageFormModel
        .getTemplateSelectElement()
        .prop("disabled", false);
}