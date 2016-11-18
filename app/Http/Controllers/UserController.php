<?php

namespace App\Http\Controllers;


use App\User;


use DB;
use Validator;
use Auth;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends Controller
{

	public function __construct()
	{
	   $this->middleware('jwt.auth');
	}
	 
    public function index(Request $request)
    {
		if (empty($request->search_all)) {
			$query = "select a.thai_full_name, a.position_name, d.operation_name, b.desc_1 own_cost_center,e.ccdef revised_ccdef, e.desc_1 revised_cost_center, f.role_id, f.role_name, a.active_flag
			from dqs_user a
			left outer join dqs_branch b
			on a.own_cost_center = b.ccdef
			left outer join dqs_region c
			on b.region = c.region_code
			left outer join dqs_branch_operation d
			on c.operation_id = d.operation_id
			left outer join dqs_branch e
			on a.revised_cost_center = e.ccdef
			left outer join dqs_role f
			on a.role_id = f.role_id
			where 1=1"; 
			
			$qinput = array();
			
			empty($request->personnel_id) ?: ($query .= " and a.personnel_id = ? " AND $qinput[] = $request->personnel_id);
			empty($request->own_cost_center) ?: ($query .= " and b.ccdef = ? " AND $qinput[] = $request->own_cost_center);
			empty($request->revised_cost_center) ?: ($query .= " and e.ccdef = ? " AND $qinput[] = $request->revised_cost_center);
			empty($request->role_id) ?: ($query .= " and f.role_id = ? " AND $qinput[] = $request->role_id);
			empty($request->active_flag) ?: ($query .= " and a.active_flag = ? " AND $qinput[] = $request->active_flag);
			
			// Get all items you want
			$items = DB::select($query, $qinput);
		} else {
			$q = "%" . $request->search_all . "%";
		//	$qflag = $request->search_all;
			$items = DB::select("
				select a.thai_full_name, a.position_name, d.operation_name, b.desc_1 own_cost_center,e.ccdef revised_ccdef, e.desc_1 revised_cost_center, f.role_id, f.role_name, a.active_flag
				from dqs_user a
				left outer join dqs_branch b
				on a.own_cost_center = b.ccdef
				left outer join dqs_region c
				on b.region = c.region_code
				left outer join dqs_branch_operation d
				on c.operation_id = d.operation_id
				left outer join dqs_branch e
				on a.revised_cost_center = e.ccdef
				left outer join dqs_role f
				on a.role_id = f.role_id
				where a.thai_full_name like ?
				or a.position_name like ?
				or d.operation_name like ?
				or b.desc_1 like ?
				or e.desc_1 like ?
				or f.role_name like ?
			", array($q, $q, $q, $q, $q, $q));

		}

		// Get the current page from the url if it's not set default to 1
		empty($request->page) ? $page = 1 : $page = $request->page;
		
		// Number of items per page
		empty($request->rpp) ? $perPage = 10 : $perPage = $request->rpp;

		// Start displaying items from this number;
		$offSet = ($page * $perPage) - $perPage; // Start displaying items from this number

		// Get only the items you need using array_slice (only get 10 items since that's what you need)
		$itemsForCurrentPage = array_slice($items, $offSet, $perPage, false);

		// Return the paginator with only 10 items but with the count of all items and set the it on the correct page
		$result = new LengthAwarePaginator($itemsForCurrentPage, count($items), $perPage, $page);			


		return response()->json($result);
    }
	
	public function update()
	{
	}
	
	public function auto_personnel(Request $request)
	{
		$q = '%' . $request->q . '%';
		$items = DB::select("
			select top 10 personnel_id
			from dqs_user
			where personnel_id like ?
		",array($q));
		return response()->json($items);
	}
	
	public function list_revised_cost_center()
	{
		$items = DB::select("
			select distinct ccdef, desc_1
			from dqs_branch
		");
		return response()->json($items);
	}
	
	public function auto_cost_center(Request $request)
	{
		$q = '%' . $request->q . '%';
		$items = DB::select("
			select distinct top 10 ccdef, desc_1
			from dqs_branch
			where desc_1 like ?
		", array($q));
		return response()->json($items);
	}
	
	// public function store(Request $request)
	// {
		// $user = new User;
		// $user->user_name = $request->user_name;
		// $user->password = bcrypt($request->password);
		// $user->own_cost_center = 1;
		// $user->revised_cost_center = 1;
		// $user->position = 'Admin';
		// $user->super_flag = 1;
		// $user->active_flag = 1;
		// $user->role_id = 1;
		// $user->save();
		// return response()->json($user);
	// }
	
	
}