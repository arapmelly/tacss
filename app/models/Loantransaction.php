<?php

class Loantransaction extends \Eloquent {

	// Add your validation rules here
	public static $rules = [
		// 'title' => 'required'
	];

	// Don't forget to fill this array
	protected $fillable = [];


	public function loanaccount(){

		return $this->belongsTo('Loanaccount');
	}



	public static function getLoanBalance($loanaccount){

	


		//$principal_paid = Loanrepayment::getPrincipalPaid($loanaccount);
		//$interest_paid = Loanrepayment::getInterestPaid($loanaccount);

		//$total_paid = $principal_paid + $interest_paid;

		//loan_amount = Loanaccount::getLoanAmount($loanaccount);

		//$balance = $loan_amount - $total_paid;

		$payments = DB::table('loantransactions')->where('loanaccount_id', '=', $loanaccount->id)->where('type', '=', 'credit')->sum('amount');

		
		$loanamount = Loanaccount::getLoanAmount($loanaccount);
		$balance = $loanamount - $payments;
		return $balance;
		


	}


	public static function getLoanBalanceTacsix($loanaccount){

	


		

		$payments = DB::table('loantransactions')->where('loanaccount_id', '=', $loanaccount->id)->where('type', '=', 'credit')->sum('amount');

		
		$loanamount = Loanaccount::getLoanAmountTacsix($loanaccount);
		$balance = $loanamount - $payments;
		return $balance;
		


	}



	public static function getRemainingPeriod($loanaccount){

		$paid_periods = DB::table('loantransactions')->where('loanaccount_id', '=', $loanaccount->id)->where('description', '=', 'loan repayment')->count();

		$remaining_period = $loanaccount->repayment_duration - $paid_periods;

		return $remaining_period;
	}


	public static function getPrincipalDueTacsix($loanaccount){

		$principal = $loanaccount->amount_disbursed;
		$time = $loanaccount->repayment_duration;

		$amount = $principal / $time;

		return $amount;


	}


	public static function getInterestDueTacsix($loanaccount){

		$interest = Loanaccount::getInterestAmountTacsix($loanaccount);
		$time = $loanaccount->repayment_duration;

		$amount = $interest / $time;

		return $amount;


	}


	public static function getDurationDueTacsix($loanaccount){

		$principal_due = getPrincipalDueTacsix($loanaccount);
		$interest_due = getInterestDueTacsix($loanaccount);

		$amount = $principal_due + $interest_due;

		return $amount;


	}


	public static function getRepaymentsMade($loanaccount){

		$repayments = DB::table('loantransactions')->where('loanaccount_id', '=', $loanaccount->id)->where('type', '=', 'credit')->count();

		return $repayments;
	}


	public static function getPrincipalBalanceTacsix($loanaccount){

		$principal_due = Loantransaction::getPrincipalDueTacsix($loanaccount);

		$repayments_made = Loantransaction::getRepaymentsMade($loanaccount);

		$principal_paid = $principal_due * $repayments_made;

		$principal = $loanaccount->amount_disbursed;

		$balance = $principal - $principal_paid;

		return $balance;
	}


	public static function getOffsetAmount($loanaccount){

	

		$principal_balance = Loantransaction::getPrincipalBalanceTacsix($loanaccount);

		$interest_due = $principal_balance * ($loanaccount->interest_rate/100);

		$offsetamount = $principal_balance + $interest_due;

		return $offsetamount;
	}





	public static function getPrincipalDue($loanaccount){


		$remaining_period = Loantransaction::getRemainingPeriod($loanaccount);

		$principal_paid = Loanrepayment::getPrincipalPaid($loanaccount);

		$principal_balance = $loanaccount->amount_disbursed - $principal_paid;
		
		$principal_due = 0;

		if($loanaccount->loanproduct->formula == 'RB'){

			$principal_due = $principal_balance / $remaining_period;	

		}



		// get principal due on Straight Line
		if($loanaccount->loanproduct->formula == 'SL'){


			//if($loanaccount->loanproduct->amortization == 'EP
			if($principal_balance > 0 && $remaining_period > 0){
				$principal_due = $principal_balance / $remaining_period;
			}
				
			//}

			//if($loanaccount->loanproduct->amortization == 'EI'){

				//$principal_due = $loanaccount->amount_disbursed / $loanaccount->repayment_duration;
			//}
			


		}


		return $principal_due;

	}




	public static function getInterestDue($loanaccount){


		$remaining_period = Loantransaction::getRemainingPeriod($loanaccount);

		$principal_paid = Loanrepayment::getPrincipalPaid($loanaccount);

		$principal_balance = $loanaccount->amount_disbursed - $principal_paid;


		if($loanaccount->loanproduct->formula == 'RB'){

			$interest_due = ($principal_balance * ($loanaccount->interest_rate/100));	

		}



		// get principal due on Straight Line
		if($loanaccount->loanproduct->formula == 'SL'){

			$interest_amount = Loanaccount::getInterestAmount($loanaccount);

			$interest_paid = Loanrepayment::getInterestPaid($loanaccount);

			$interest_balance = $interest_amount - $interest_paid;
			
			$interest_due = 0;


			if($interest_balance > 0 && $remaining_period > 0){
				$interest_due = $interest_balance / $remaining_period;
			}
			

			//if($loanaccount->loanproduct->amortization == 'EI'){

				//$interest_due = $interest_amount / $loanaccount->repayment_duration;
			//}


		}


		return $interest_due;



	}


	public static function repayLoan($loanaccount, $amount, $date){

		$transaction = new Loantransaction;

		$transaction->loanaccount()->associate($loanaccount);
		$transaction->date = $date;
		$transaction->description = 'loan repayment';
		$transaction->amount = $amount;
		$transaction->type = 'credit';
		$transaction->save();

		Audit::logAudit($date, Confide::user()->username, 'loan repayment', 'Loans', $amount);



	}



	public static function disburseLoan($loanaccount, $amount, $date){

		$transaction = new Loantransaction;

		$transaction->loanaccount()->associate($loanaccount);
		$transaction->date = $date;
		$transaction->description = 'loan disbursement';
		$transaction->amount = $amount;
		$transaction->type = 'debit';
		$transaction->save();


		$account = Loanposting::getPostingAccount($loanaccount->loanproduct, 'disbursal');

		$data = array(
			'credit_account' =>$account['credit'] , 
			'debit_account' =>$account['debit'] ,
			'date' => $date,
			'amount' => $loanaccount->amount_disbursed,
			'initiated_by' => 'system',
			'description' => 'loan disbursement'

			);


		$journal = new Journal;


		$journal->journal_entry($data);

		Audit::logAudit($date, Confide::user()->username, 'loan disbursement', 'Loans', $amount);



	}




	public static function topupLoan($loanaccount, $amount, $date){

		$transaction = new Loantransaction;

		$transaction->loanaccount()->associate($loanaccount);
		$transaction->date = $date;
		$transaction->description = 'loan top up';
		$transaction->amount = $amount;
		$transaction->type = 'debit';
		$transaction->save();


		$account = Loanposting::getPostingAccount($loanaccount->loanproduct, 'disbursal');

		$data = array(
			'credit_account' =>$account['credit'] , 
			'debit_account' =>$account['debit'] ,
			'date' => $date,
			'amount' => $loanaccount->top_up_amount,
			'initiated_by' => 'system',
			'description' => 'loan top up'

			);


		$journal = new Journal;


		$journal->journal_entry($data);

		Audit::logAudit($date, Confide::user()->username, 'loan to up', 'Loans', $amount);



	}



	public static function trasactionExists($date, $loanaccount){


		$dt = explode('-', $date);
		$mnth = $dt[1];

		$dates = DB::table('loantransactions')->where('loanaccount_id', '=', $loanaccount)->get();

		foreach ($dates as $date) {

			$dat = explode('-', $date->date);
			$month = $dat[1];
			
			if($mnth == $month){

				return true;
			} 

		}

		
	}




}