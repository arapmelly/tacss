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


<div class="col-lg-5">
  
  <form method="POST" action="{{{ URL::to('rollover/getstatus') }}}"  enctype="multipart/form-data">
 



         <div class="form-group " >

            <label>Choose Year Period</label>
            <input type="text" class="form-control datepicker7" readonly="readonly" name="year">

         </div>


        

        <div class="form-actions form-group">
        
          <button type="submit" class="btn btn-primary btn-sm">Show Status</button>
        </div>


</form> 

</div>




<br>
</div>	


<div class="col-lg-12 ">
<hr>
<p>Rollover Status for year: {{ $year}} </p>
  <table class="table table-condensed table-bordered">
      
      <thead>
        <th>Products</th>
        @foreach($months as $month)
        <th>{{$month}}</th>
        @endforeach
      </thead>

      <tbody>
        
        @foreach($savingproducts as $saving)
        <tr>
          <td>{{$saving->name}}</td>
          
          @foreach($monthdigits as $mnth)

          <?php $period = $mnth.'-'.$year; ?>
          @if(Autoprocess::checkProcessed($period, 'saving', $saving))
          <td> <i class="fa fa-check"></i> </td>
          @else
          <td></td>
          @endif
          
          @endforeach
        </tr>
        @endforeach

        @foreach($loanproducts as $loanproduct)
        <tr>
          <td>{{$loanproduct->name}}</td>
          
          @foreach($monthdigits as $mnth)

          <?php $period = $mnth.'-'.$year; ?>
          @if(Autoprocess::checkProcessed($period, 'loan', $loanproduct))
          <td><i class="fa fa-check"></i>  </td>
         @else
          <td></td>
          @endif
         
          @endforeach
        </tr>
        @endforeach

      </tbody>

  </table>	


</div>	



</div>





@stop