var OpencartMundipaggLoader = function (mundipaggLoader) {
    this.mundipaggLoader = mundipaggLoader;

    var baseUrl = window.location.href;
    baseUrl = baseUrl.split('?route=');
    var token = baseUrl[1].split('user_token=')
        [1].split('&')[0];
    this.baseUrl = baseUrl[0]  + '?user_token=' + token;
};

OpencartMundipaggLoader.prototype.loadLocation = function() {
    var url = this.baseUrl + '&route=extension/payment/mundipagg/location';
    $.ajax({
        url,
        success: function(result) {
            this.mundipaggLoader.Location = result;
            this.mundipaggLoader.componentLoadStatus.Location.error = false;
        }.bind(this),
        error: function(result) {
            this.mundipaggLoader.componentLoadStatus.Location.error =
                'Location component load failure!';
            console.error("error",result.responseJSON);
        },
        complete: function() {
            this.mundipaggLoader.componentLoadStatus.Location.processed = true;
            if (this.mundipaggLoader.isLoadEnded()) {
                this.mundipaggLoader.loadEndCallback();
            }
        }.bind(this)
    });
};