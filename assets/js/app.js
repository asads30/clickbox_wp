(function($) {
    $(window).on('load', function(){
        var api_host = 'https://app.clickbox.uz/api';
        var url_cells = '/merchant/handbooks/cell-types';
        var url_pochtamats = '/merchant/handbooks/postomats';
        var currentLang = 'ru'; // currentLangGET
        var cell_input = 4; // Select minimal tarifs
        var lists = [];
        var clickboxModal = new tingle.modal({
            footer: false,
            cssClass: ['clickbox-modal'],
            closeLabel: '',
        });
        var clickboxModalPlace = new tingle.modal({
            footer: false,
            closeLabel: '',
            closeMethods: [],
            cssClass: ['clickbox-modal', 'clickbox-modal-place'],
        });
        clickboxModal.setContent('<div class="pochtomat-box"><div id="pochtomat-list"></div><div id="pochtamat-map"></div></div>');
        clickboxModalPlace.setContent('<div id="pochtamat-place"></div>');
        let goBackText = currentLang == 'uz' ? 'Ortga qaytish' : 'Вернуться назад';
        let goSubmitText = currentLang == 'uz' ? 'Tasdiqlash' : 'Подтвердить';
        let leaveText = currentLang == 'uz' ? 'Qoldirish' : 'Оставить';
        let editText = currentLang == 'uz' ? 'O\'zgartirish' : 'Изменить';
        let submitText = currentLang == 'uz' ? 'Tanlash' : 'Выбрать';
        let cancelText = currentLang == 'uz' ? 'Bekor qilish' : 'Отменить';
        let addressText = currentLang == 'uz' ? 'Manzil' : 'Адрес';
        let getThereText = currentLang == 'uz' ? 'Qanday borsa bo\'ladi' : 'Как добраться';
        let referenceText = currentLang == 'uz' ? 'Mo\'ljal' : 'Ориентир';
        let whyFind = currentLang == 'uz' ? 'Qanday topish mumkin' : 'Как найти';
        let workTime = currentLang == 'uz' ? 'Ish vaqti' : 'Режим работы';
        let error1 = currentLang == 'uz' ? 'Afsuski, bo\'sh pochtomat yo\'q. Mahsulotlarni savatchadan kamaytiring (agar ko\'p bo\'lsa)' : 'К сожалению свободных почтоматов не осталось. Попробуйте удалить товары с корзины (если их много)';
        let error2 = currentLang == 'uz' ? 'HTTP xato. Iltimos, qo\'llab-quvvatlash xizmatiga qo\'ng\'iroq qiling' : 'Ошибка HTTP. Пожалуйста, позвоните к службу поддержки';
        let choosePcht = currentLang == 'uz' ? 'Pochtomat tanlang' : 'Выберите почтомат';
        function init(pochtamats = null) {
            $('#pochtamat-map').html('');
            $('#pochtamat-place').html('');
            var myMap = new ymaps.Map("pochtamat-map", {
                center: [41.31688073, 69.24690049],
                zoom: 11,
            });
			var menu = $('<div class="pochtomats-list"></ul>');
            if (pochtamats) {
                pochtamats.map((pochtamat, index) => {
					var menuItem = $('<div class="pochtomats-item"><div class="pochtomats-address">' + pochtamat.address + '</div><div class="pochtomats-name">' + pochtamat.name + '</div><div class="pochtomats-des">' + pochtamat.description + '</div></div>');
        				menuItem.appendTo(menu);
						menu.appendTo($('#pochtomat-list'));
                    var marker = new ymaps.Placemark(
                        [pochtamat.loc_latitude, pochtamat.loc_longitude], {
                            balloonContentBody: '<span>'+pochtamat.name+'</span>',
                        }, {
                            preset: 'islands#blueDotIcon',
                            iconColor: '#0095b6'
                        });
                    marker.events.add('balloonopen', function addPochtomat(e) {
                        let pcht1 = '<h4 class="pchtName">'+pochtamat.name+'</h4><p class="pchtDes"><span>' + addressText + ': </span>'+pochtamat.address+'</p>';
                        let imagePochtomat = (pochtamat.images[0]) ? '<div class="pchtImage"><img src="'+pochtamat.images[0]+'" class="pchtImg" width="100%"></div>' : '<div class="pchtImage"><img src="https://www.spot.uz/media/img/2021/11/B6LGmS16375611296395_b.jpg" class="pchtImg" width="100%"></div>'
                        let pcht2 = '<div class="pchtBox">'+imagePochtomat+'<div class="pchtText"><p class="pchtMarsh"><span>' + getThereText + ': </span>'+pochtamat.instruction+'</p><p class="pchtMarsh"><span>' + referenceText + ': </span>'+pochtamat.reference_point+'</p><p class="pchtMarsh"><span>' + whyFind + ': </span>'+pochtamat.location+'</p><p class="pchtMarsh"><span>' + workTime + ': </span>'+pochtamat.working_hours+'</p></div></div>';
                        let pcht3 = pochtamat.id == $('#clickbox_pochtomatid').val() ? '<div class="pchtBtns"><button class="pcht-back">' + goBackText + '</button><button type="button" class="btn-pochtamat btn-danger" data-address="'+pochtamat.address+'" data-lng="'+pochtamat.loc_longitude+'" data-lat="'+pochtamat.loc_latitude+'" data-id="'+pochtamat.id+'" data-state="1" id="pcht-edit">' + leaveText + '</button></div>' : '<div class="pchtBtns"><button class="pcht-back">' + goBackText + '</button><button type="button" class="btn-pochtamat btn-success" data-address="'+pochtamat.address+'" data-lng="'+pochtamat.loc_longitude+'" data-lat="'+pochtamat.loc_latitude+'" data-id="'+pochtamat.id+'" data-state="1" id="pcht-select">' + goSubmitText + '</button></div>';
                        clickboxModal.close();
						clickboxModalPlace.open();
                        $('.clickbox-modal-place').attr('data-step', '01');
                        $('#pochtamat-place').html(pcht1+pcht2+pcht3);
						$('#pochtomat-list').html('<span></span>');
                    });
                    myMap.geoObjects.add(marker);
					menuItem.find('.pochtomats-address').parent().bind('click', function(){
						let pcht1 = '<h4 class="pchtName">'+pochtamat.name+'</h4><p class="pchtDes"><span>' + addressText + ': </span>'+pochtamat.address+'</p>';
                        let imagePochtomat = (pochtamat.images[0]) ? '<div class="pchtImage"><img src="'+pochtamat.images[0]+'" class="pchtImg" width="100%"></div>' : '<div class="pchtImage"><img src="https://www.spot.uz/media/img/2021/11/B6LGmS16375611296395_b.jpg" class="pchtImg" width="100%"></div>'
                        let pcht2 = '<div class="pchtBox">'+imagePochtomat+'<div class="pchtText"><p class="pchtMarsh"><span>' + getThereText + ': </span>'+pochtamat.instruction+'</p><p class="pchtMarsh"><span>' + referenceText + ': </span>'+pochtamat.reference_point+'</p><p class="pchtMarsh"><span>' + whyFind + ': </span>'+pochtamat.location+'</p><p class="pchtMarsh"><span>' + workTime + ': </span>'+pochtamat.working_hours+'</p></div></div>';
                        let pcht3 = pochtamat.id == $('#clickbox_pochtomatid').val() ? '<div class="pchtBtns"><button class="pcht-back">' + goBackText + '</button><button type="button" class="btn-pochtamat btn-danger" data-address="'+pochtamat.address+'" data-lng="'+pochtamat.loc_longitude+'" data-lat="'+pochtamat.loc_latitude+'" data-id="'+pochtamat.id+'" data-state="1" id="pcht-edit">' + leaveText + '</button></div>' : '<div class="pchtBtns"><button class="pcht-back">' + goBackText + '</button><button type="button" class="btn-pochtamat btn-success" data-address="'+pochtamat.address+'" data-lng="'+pochtamat.loc_longitude+'" data-lat="'+pochtamat.loc_latitude+'" data-id="'+pochtamat.id+'" data-state="1" id="pcht-select">' + goSubmitText + '</button></div>';
                        clickboxModal.close();
						clickboxModalPlace.open();
                        $('.clickbox-modal-place').attr('data-step', '01');
                        $('#pochtamat-place').html(pcht1+pcht2+pcht3);
						$('#pochtomat-list').html('<span></span>');
					});
                });
            }
        }
        let clickboxBtn = document.getElementById('clickbox-btn');
        if(clickboxBtn){
            clickboxBtn.addEventListener('click', function(){
                if(cell_input > 0){
                    clickboxModal.open();
                    fetch(
                        api_host+url_pochtamats,
                        {
                            method: 'GET',
                            headers: {
                                'Authorization': 'Basic MTo1ZGU0ZmI3MjcyMGFl',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'Accept-Language': currentLang
                            }
                        }
                    )
                    .then(async (response) => {
                        if (response.ok) {
                            let json = await response.json();
                            if (json.data.length > 0) {
                                $('#pochtamat-map').show();
                                ymaps.ready(init(json.data));
                            } else {
                                $('#pochtamat-map').html('<h4>' + error1 + '</h4>');
                            }
                        } else {
                            $('#pochtamat-map').html('<h4>' + error2 + '</h4>');
                        }
                    })
                    .catch((error) => {
                        $('#pochtamat-map').html('<h4>' + error2 + '</h4>');
                    });
                }
            });
        }
        $(document).on('click', '.btn-pochtamat', function() {
            $('#clickbox-btn').text(submitText);
            $('#clickbox-edit').text(choosePcht);
            clickboxModalPlace.close();
            $('#billing_contactmethod').val('Test: ' + 5);
            if ($(this).data('state') == 1) {
                fetch(
                    api_host+url_cells + '?postomat_id=' + $(this).data('id'),
                    {
                        method: 'GET',
                        headers: {
                            'Authorization': 'Basic MTo1ZGU0ZmI3MjcyMGFl',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'Accept-Language': currentLang
                        }
                    }
                )
                .then(async (response) => {
                    if (response.ok) {
                        let json = await response.json();
                        lists = json.data;
                        let trueSizes = lists.filter(function(item){
                            return item.is_free == true;
                        });
                        if(trueSizes.length == 0){
                            alert('Свободных ячеек не осталось');
                        }
                        if(cell_input == 2 && trueSizes.length == 4){
                            $('#clickbox_celltype').val(trueSizes[1].id);
                            $('#clickbox_dimensionz').val(trueSizes[1].size_z);
                        } else if(cell_input == 3 && trueSizes.length == 4){
                            $('#clickbox_celltype').val(trueSizes[2].id);
                            $('#clickbox_dimensionz').val(trueSizes[2].size_z);
                        } else if(cell_input == 3 && trueSizes.length == 3){
                            $('#clickbox_celltype').val(trueSizes[1].id);
                            $('#clickbox_dimensionz').val(trueSizes[1].size_z);
                        } else if(cell_input == 4 && trueSizes.length == 4){
                            $('#clickbox_celltype').val(trueSizes[3].id);
                            $('#clickbox_dimensionz').val(trueSizes[3].size_z);
                        } else if(cell_input == 4 && trueSizes.length == 3){
                            $('#clickbox_celltype').val(trueSizes[2].id);
                            $('#clickbox_dimensionz').val(trueSizes[2].size_z);
                        } else if(cell_input == 4 && trueSizes.length == 2){
                            $('#clickbox_celltype').val(trueSizes[1].id);
                            $('#clickbox_dimensionz').val(trueSizes[1].size_z);
                        } else{
                            $('#clickbox_celltype').val(trueSizes[0].id);
                            $('#clickbox_dimensionz').val(trueSizes[0].size_z);
                        }
                    } else {
                        alert("Ошибка HTTP: " + response.status);
                    }
                })
                .catch();
                $('#clickbox_pochtomatid').val($(this).data('id'));
                $('.btn-pochtamat').addClass('btn-success').removeClass('btn-danger').data('state', 0).text(submitText);
                $(this).addClass('btn-danger').removeClass('btn-success').data('state', 1).text(cancelText);
                $('#clickbox-edit').text($(this).data('address'));
                $('#billing_address_1').val($(this).data('address'));
                $('#clickbox-btn').text(editText);
            } else {
                $(this).data('state', 0).text(submitText);
                $('#clickbox_pochtomatid').val('');
            }
            return false;
        });
        $(document).on('click', '.pcht-back', function() {
            clickboxModalPlace.close();
            if(cell_input > 0){
                clickboxModal.open();
                fetch(
                    api_host+url_pochtamats,
                    {
                        method: 'GET',
                        headers: {
                            'Authorization': 'Basic MTo1ZGU0ZmI3MjcyMGFl',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'Accept-Language': currentLang
                        }
                    }
                )
                .then(async (response) => {
                    if (response.ok) {
                        let json = await response.json();
                        if (json.data.length > 0) {
                            $('#pochtamat-map').show();
                            ymaps.ready(init(json.data));
                        } else {
                            $('#pochtamat-map').html('<h4>' + error1 + '</h4>');
                        }
                    } else {
                        $('#pochtamat-map').html('<h4>' + error2 + '</h4>');
                    }
                })
                .catch((error) => {
                    $('#pochtamat-map').html('<h4>' + error2 + '</h4>');
                });
            }
        });
    });
})(jQuery);