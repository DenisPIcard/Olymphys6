function changejure(j) {//j est l'objet input qui a lancé la fonction, pour le formulaire de gestion des jures des cia

    var data_value = j.value;
    var data_type = j.name;
    var id_jure = j.id.split(data_type)[1];
    var formURL = document.getElementsByTagName('form')[2].action;
    $.ajax({
        url: formURL,
        type: "POST",
        data: {value: data_value, type: data_type, idjure: id_jure},

        success: function () {
            document.querySelector('#gestionjures').click()
        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    });


}

function changejurecn(j) {//j est l'objet input qui a lancé la fonction, pour le formulaire de gestion des jures des cia

    var data_value = j.value;
    var data_type = j.name;
    var id_jure = j.id.split(data_type)[1];
    var formURL = document.getElementsByTagName('form')[3].action;

    $.ajax({
        url: formURL,
        type: "POST",
        data: {value: data_value, type: data_type, idjure: id_jure},

        success: function () {
            document.querySelector('#gestionjures').click()
        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    });


}

function changeequipe(e, i, j) {
    var type = 'equipe';
    var data_value = e.value;
    var id_equipe = i;
    var id_jure = j;
    console.log(data_value);
    var formURL = document.getElementsByName('forme'.concat(id_equipe))[0].action;
    console.log(formURL);
    $.ajax({
        url: formURL,
        type: "GET",
        data: {type: type, value: data_value, idequipe: id_equipe, idjure: id_jure},

        success: function () {
            document.querySelector('#gestionjures').click()

        },

        error: function (xhr, status, error) {
            alert(xhr.responseText);
        }
    });


}
function changeequipecia(e, i, j) {
    var type = 'equipe';
    var data_value = e.value;
    var id_equipe = i;
    var id_jure = j;
    console.log(data_value);
    var formURL = document.getElementById('form-'.concat(id_jure))[0].action;

    console.log(formURL);
    $.ajax({
        url: formURL,
        type: "GET",
        data: {type: type, value: data_value, idequipe: id_equipe, idjure: id_jure},

        success: function () {
            document.querySelector('#gestionjures').click()

        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    });


}

$('#modalconfirmjure').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget) // Button that triggered the modal
    var recipient = button.data('idjure');

    var modal = $(this)
    modal.find('.modal-title').text('Attention!!!!')
    modal.find('.modal-body input').val(recipient)
});
$('#modalconfirmjurecn').on('show.bs.modal', function (event) {//envoi des conseils jury cia
    var button = $(event.relatedTarget) // Button that triggered the modal
    var recipient = button.data('idjure');

    var modal = $(this)
    modal.find('.modal-title').text('Attention!!!!')
    modal.find('.modal-body input').val(recipient)
});
$('#modalenvoiconseilscn').on('show.bs.modal', function (event) {//envoi des recommandations jury du cn
    var button = $(event.relatedTarget) // Button that triggered the modal
    var recipient = button.data('id');
    console.log(recipient)
    var modal = $(this)
    modal.find('.modal-title').text('Attention')
    modal.find('.modal-body input').val(recipient)
});
$('#modalenvoiconseils').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget) // Button that triggered the modal
    var recipient = button.data('idequipe');

    var modal = $(this)
    modal.find('.modal-title').text('Attention')
    modal.find('.modal-body input').val(recipient)
});

function confirmer() {

    console.log('OK');
    var form = document.getElementsByName("form1");
    console.log(form)
    var formURL = "/secretariatjuryCia/confirm_gestion_jures"//document.getElementsByTagName('form')[0].action;
    var prenoms = [];
    for (i = 0; i < form.length; i++) {
        prenoms[i] = form[i].getElementsByTagName('input')
        console.log(prenoms[i]);

    }


    $.ajax({
        url: formURL,
        type: "POST",
        data: {values: prenoms},

        success: function () {
            document.querySelector('#gestionjures').click()

        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    });
}

function modifheure(j) {//j est l'objet input qui a lancé la fonction, pour le formulaire de gestion des jures des cia


    var data_type = j.name;
    var id_equipe = j.id.split(data_type)[1];
    var data_value = j.value;
    var formURL = document.getElementById('formhoraires').action;


    console.log(data_type);
    console.log(id_equipe);

    console.log(data_value);
    $.ajax({
        url: formURL,
        type: "GET",
        data: {value: data_value, type: data_type, idequipe: id_equipe},

        success: function () {
            document.querySelector('#gestionjures').click()//Recharge la page pour actualiser l'affichage
        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    });


}

function modifsalle(j) {//j est l'objet input qui a lancé la fonction, pour le formulaire de gestion des jures des cia

    var data_value = j.value;
    var data_type = j.name;
    var id_equipe = j.id.split(data_type)[1];

    var formURL = document.getElementById('formsalles').action;
    console.log(data_type);
    console.log(id_equipe);

    console.log(data_value);

    $.ajax({
        url: formURL,
        type: "GET",
        data: {value: data_value, type: data_type, idequipe: id_equipe},

        success: function () {
            document.querySelector('#gestionjures').click()
        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    });


}

function modifordre(j) {//j est l'objet input qui a lancé la fonction, pour le formulaire de gestion des jures des cia

    var data_value = j.value;
    var data_type = j.name;
    var id_equipe = j.id.split(data_type)[1];

    var formURL = document.getElementById('formordre').action;
    console.log(data_type);
    console.log(id_equipe);

    console.log(data_value);

    $.ajax({
        url: formURL,
        type: "GET",
        data: {value: data_value, type: data_type, idequipe: id_equipe},

        success: function () {
            document.querySelector('#gestionjures').click()
        },

        error: function (data) {
            alert("Error while submitting Data");
        },
    });


}

