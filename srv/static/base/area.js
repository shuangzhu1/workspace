/**
 * Created by ykuang on 15-6-17.
 */
function Area(provinceElm, cityEle, countyEle) {
    this.provinceElm = provinceElm;
    this.cityEle = cityEle;
    this.countyEle = countyEle;
}

Area.prototype = {
    init: function (provinceCode, cityCode, countyCode) {
        this.getProvince(provinceCode, cityCode, countyCode);
        // this.getCity(provinceElm, cityEle, countyEle, provinceCode, cityCode);
        //this.getCounty(provinceElm, cityEle, countyEle, cityCode, countyCode);
    },
    changeProvince: function () {
        var __this = this;
        $(__this.provinceElm).on('change', function () {
            var provinceCode = $(this).val();
            __this.getCity(provinceCode, 0, 0);
            $(__this.countyEle).html("<option value='0'>--请选择县乡--</option>");
        });
    },
    changeCity: function () {
        var __this = this;
        $(__this.cityEle).on('change', function () {
            var provinceCode = $(__this.provinceElm).val();
            var cityCode = $(__this.cityEle).val();
            __this.getCounty(provinceCode, cityCode, 0);
        });
    },
    getProvince: function (provinceCode, cityCode, countyCode) {
        var __this = this;
        requestApi('/api/area/getProvince', {code: provinceCode}, function (res) {
            if (res.result == 1) {
                var data = res.data;
                var html = "";
                if (provinceCode == 0) {
                    html = "<option value='0' selected>--请选择省份--</option>";
                } else {
                    html = "<option value='0'>--请选择省份--</option>";
                }
                for (var i in data) {
                    if (provinceCode == data[i]['code']) {
                        html += "<option value='" + data[i]['code'] + "' selected>" + data[i]['name'] + "</option>"
                    } else {
                        html += "<option value='" + data[i]['code'] + "' >" + data[i]['name'] + "</option>"
                    }
                }
                $(__this.provinceElm).html(html);
                __this.getCity(provinceCode, cityCode, countyCode);

            }
        });
    },
    getCity: function (provinceCode, cityCode, countyCode) {
        var __this = this;
        if (provinceCode == 0) {
            $(this.cityEle).html("<option value='0' selected>--请选择市区--</option>");
        } else {
            requestApi('/api/area/getCity', {code: provinceCode, cityCode: cityCode}, function (res) {
                if (res.result == 1) {
                    var data = res.data;
                    var html = "";
                    if (cityCode == 0) {
                        html = "<option value='0' selected>--请选择市区--</option>";
                    } else {
                        html = "<option value='0'>--请选择市区--</option>";
                    }
                    for (var i in data) {
                        if (cityCode == data[i]['code']) {
                            html += "<option value='" + data[i]['code'] + "' selected>" + data[i]['name'] + "</option>"
                        } else {
                            html += "<option value='" + data[i]['code'] + "' >" + data[i]['name'] + "</option>"
                        }
                    }
                    $(__this.cityEle).html(html);
                    __this.getCounty(provinceCode, cityCode, countyCode);

                }
            });
        }

    },
    getCounty: function (provinceCode, cityCode, countyCode) {
        var __this = this;
        if (cityCode == 0) {
            $(this.countyEle).html("<option value='0'>--请选择县乡--</option>");
        } else {
            requestApi('/api/area/getCounty', {code: cityCode, countyCode: countyCode}, function (res) {
                if (res.result == 1) {
                    var data = res.data;
                    var html = "";
                    if (countyCode == 0) {
                        html = "<option value='0' selected>--请选择县乡--</option>";
                    } else {
                        html = "<option value='0'>--请选择县乡--</option>";
                    }
                    for (var i in data) {
                        if (countyCode == data[i]['code']) {
                            html += "<option value='" + data[i]['code'] + "' selected>" + data[i]['name'] + "</option>"
                        } else {
                            html += "<option value='" + data[i]['code'] + "' >" + data[i]['name'] + "</option>"
                        }
                    }
                    $(__this.countyEle).html(html);
                }
            });
        }
    }
};