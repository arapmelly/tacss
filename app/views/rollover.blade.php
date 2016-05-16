@extends('layouts.system')
@section('content')
<br/><br/>

<div class="row">
	<div class="col-lg-1">



</div>	

<div class="col-lg-12">

	@if (Session::get('error'))
            <div class="alert alert-danger">{{{ Session::get('error') }}}</div>
        @endif

        @if (Session::get('notice'))
            <div class="alert alert-info">{{{ Session::get('notice') }}}</div>
        @endif

<p>Roll over status </p>
<hr>

<br>
</div>	


<div class="col-lg-5 ">

	
<form method="POST" action="{{{ URL::to('rollover/getstatus') }}}"  enctype="multipart/form-data">
 

<input type="hidden" value="loans" name="category">




        


         <div class="form-group " >

            <label>Choose Year Period</label>
            <input type="text" class="form-control datepicker7" readonly="readonly" name="year">

         </div>


        

        <div class="form-actions form-group">
        
          <button type="submit" class="btn btn-primary btn-sm">Show Status</button>
        </div>


</form>	

</div>	



</div>





@stop