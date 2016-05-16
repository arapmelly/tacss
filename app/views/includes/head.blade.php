<!DOCTYPE html>
<html>



<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>XARA CBS</title>

    <!-- Core CSS - Include with every page -->
    {{ HTML::style('css/bootstrap.min.css') }}
    
   
   {{ HTML::style('font-awesome/css/font-awesome.css') }}
  

    <!-- Page-Level Plugin CSS - Blank -->

    <!-- SB Admin CSS - Include with every page -->
   
    {{ HTML::style('css/sb-admin.css') }}


    <!-- datatables css -->

    {{ HTML::style('media/css/jquery.dataTables.min.css') }}
    {{ HTML::style('datepicker/css/bootstrap-datepicker.css') }}


   

        


    <!-- jquery scripts with datatable scripts -->

    
     {{ HTML::script('media/js/jquery.js') }}

    {{ HTML::script('media/js/jquery.dataTables.js') }}
    {{ HTML::script('datepicker/js/bootstrap-datepicker.js') }}
    
   <script type="text/javascript">

  $(document).ready(function() {
    $('#users').DataTable({
        aaSorting: [] 
    });

    $('#auto').DataTable({
        aaSorting: [],
        paging: false 
    });
    $('#mobile').DataTable();
    $('#rejected').DataTable();
    $('#app').DataTable();
    $('#disbursed').DataTable();
    $('#amended').DataTable();

	} );

 
  

</script>



<script type="text/javascript">

$(function(){
$('.datepicker').datepicker({
    format: 'yyyy-mm-dd',
    autoclose: true
});
});

</script>





<script type="text/javascript">
$(function(){
$('.datepicker2').datepicker({
    format: "mm-yyyy",
    startView: "months", 
    minViewMode: "months",
    autoclose: true
});
});
</script>

<script type="text/javascript">
$(function(){
$('.datepicker7').datepicker({
    format: "yyyy",
    startView: "years", 
    minViewMode: "years",
    autoclose: true
});
});
</script>


</head>