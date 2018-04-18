jQuery(document).ready(function($) {

  var aff_options = {

    init: function() {
      this.load_id_distributor();
    },

    load_id_distributor: function() {


      var data = {
  			'action': 'load_users_id',
  		};

  		jQuery.post(aff_data.ajax_url, data, function(response) {
           response = $.parseJSON(response);

  			   //console.log(response);

           $("[name$='["+aff_data.unique_refferal_id+"]']").html(response);
           $("[name$='["+aff_data.unique_refferal_id+"]']").select2();
  		});

      $('#reff_table').DataTable({
        paging: false
      });


    }

  }

  aff_options.init();

})
