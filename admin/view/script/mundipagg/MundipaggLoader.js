var MundipaggLoader = function(loader,components) {
    this.loaderClass = loader;
    this.components = components;
    return this;
};

MundipaggLoader.prototype.load = function(callback) {
    this.loader = new this.loaderClass(this);
    this.loadEndCallback = callback;
    this.componentLoadStatus = {};

    this.components.forEach(function(component){
        this.componentLoadStatus[component] = {
            processed:false
        };
    }.bind(this));

    this.components.forEach(function(component){
        try {
            this.loader['load' + component]();
        }catch(e) {
            var error = "MundipaggLoader: loader method to '" +component+ "' not found!";
            console.error();
            this.componentLoadStatus[component] = {
                processed: true,
                error
            };
        }
    }.bind(this));
};

MundipaggLoader.prototype.isLoadEnded = function() {
    var loadEnded = true;
    Object.keys(this.componentLoadStatus).forEach(function(notUsed,component){
        if (this.componentLoadStatus[component].processed === false) {
            loadEnd = false;
        }
    }.bind(this,loadEnded));
    return loadEnded;
};