<?php

namespace App\Http\Controllers\RouteHandler;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Khill\Lavacharts\Lavacharts;

/**
 * Route Handler for Dashboard.
 *
 * @author  Azis Hapidin <azishapidin@gmail.com>
 *
 * @link    https://azishapidin.com/
 */
class HomeController extends Controller
{
    /**
     * Set Lavachart Variable as Global.
     *
     * @var \Khill\Lavacharts\Lavacharts
     */
    protected $lava;

    /**
     * Set Data to view Variable as Global.
     *
     * @var array
     */
    protected $data;

    /**
     * Set Request POST / GET to Global.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Class Constructor.
     *
     * @param \Illuminate\Http\Request $request User Request
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->middleware('auth');
        $this->lava = new Lavacharts();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->data['account_count'] = $this->request->user()->accounts()->count();
        $this->data['transaction_count'] = $this->request->user()->transactions()->whereHas('category', function ($q) {
            $q->where('show_on_stats', 1);
        })->count();
        $this->data['credit_count'] = $this->request->user()->transactions()->whereHas('category', function ($q) {
            $q->where('show_on_stats', 1);
        })->where('type', 'cr')->count();
        $this->data['debit_count'] = $this->request->user()->transactions()->whereHas('category', function ($q) {
            $q->where('show_on_stats', 1);
        })->where('type', 'db')->count();

        // Transaction type counter
        $this->countByType();

        // Transaction category counter
        $this->sumByTransactionCategory();

        // Count by date
        $this->countByDate();

        // Pass all chart data to view
        $this->data['lava'] = $this->lava;

        return view('home', $this->data);
    }

    /**
     * Transaction counter by type (Credit or Debit) and generate chart.
     *
     * @return void
     */
    public function countByType()
    {
        $transactionCounter = $this->lava->DataTable();
        $transactionCounter->addStringColumn(__('Transaction Type'))
                ->addNumberColumn(__('Counter'))
                ->addRow([__('Credit'), $this->data['credit_count']])
                ->addRow([__('Debit'), $this->data['debit_count']]);

        $this->lava->PieChart('TypeCounter', $transactionCounter, [
            'title'  => __('Transaction Counter'),
            'is3D'   => true,
        ]);
    }

    /**
     * Sum all transaction by category and generate chart.
     *
     * @return void
     */
    public function sumByTransactionCategory()
    {
        $user = $this->request->user();
        $categoryCounter = [];
        $categories = $user->categories()->doesntHave('parent')->where('show_on_stats', 1)->get();

        foreach ($categories as $category) {
            $total = $category->transactions()->sum('amount');

            // Sum childs
            $childs = $category->child()->where('show_on_stats', 1)->get();
            foreach ($childs as $child) {
                $total += $child->transactions()->sum('amount');
            }

            $categoryCounter[] = [
                'name'  => $category->name,
                'total' => $total,
            ];
        }

        $categoryChart = $this->lava->DataTable();
        $categoryChart->addStringColumn(__('Category Name'))->addNumberColumn(__('Total'));
        foreach ($categoryCounter as $counter) {
            if ($counter['total'] == 0) {
                continue;
            }
            $categoryChart->addRow([$counter['name'], $counter['total']]);
        }

        $this->lava->BarChart('CategoryCounter', $categoryChart);
    }

    public function countByDate()
    {
        $startDate = (new Carbon('first day of this month'))->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');
        if (!is_null($this->request->get('dates'))) {
            $dates = explode(' - ', $this->request->get('dates'));
            if (count($dates) == 2) {
                $startDate = Carbon::createFromFormat('Y-F-d', $dates[0])->format('Y-m-d');
                $endDate = Carbon::createFromFormat('Y-F-d', $dates[1])->format('Y-m-d');
            }
        }

        $this->data['byDate']['from'] = $startDate;
        $this->data['byDate']['to'] = $endDate;
        $this->data['byDate']['category'] = [];

        $this->data['byDate']['credit'] = $this->request->user()->transactions()->where('type', 'cr')->whereBetween('date', [
            $startDate, $endDate,
        ])->whereHas('category', function ($q) {
            $q->where('show_on_stats', 1);
        })->sum('amount');

        $this->data['byDate']['debit'] = $this->request->user()->transactions()->where('type', 'db')->whereBetween('date', [
            $startDate, $endDate,
        ])->whereHas('category', function ($q) {
            $q->where('show_on_stats', 1);
        })->sum('amount');

        $categories = $this->request->user()->categories()->where('show_on_stats', 1)->get();
        foreach ($categories as $category) {
            $total = $category->transactions()->whereBetween('date', [
                $startDate, $endDate,
            ])->sum('amount');
            if ($total == 0) {
                continue;
            }

            $this->data['byDate']['category'][] = [
                'name'  => $category->name,
                'total' => $total,
            ];
        }
    }
}
