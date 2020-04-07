<?php
use yii\helpers\Html;
use yii\helpers\Url;
use app\helpers\HtmlHelper;
use kartik\grid\GridView;
use kartik\widgets\Select2;
use kartik\datecontrol\DateControl;
use app\models\Trips;

/* @var $this yii\web\View */
$this->title = 'Trips';

$request = Yii::$app->request;

$this->params['breadcrumbs'][] = ['label' => $this->title];
?>
<div class="row">
	<div class="col">
		<?= $this->render('@app/views/layouts/breadcrumbs') ?>
	</div>
	<?php if ($request->get('sort') || $request->get('TripsSearch')) { ?>
	<div class="col-md-auto d-lg-block d-print-none">
		<?= HtmlHelper::btn(Url::to(['trips/index']), 'Clear filter', 'btn btn-secondary btn-block btn-breadcrumb', true, 'Clear filter') ?>
	</div>
	<?php } ?>
	<div class="col-md-auto d-lg-block d-print-none">
		<?= HtmlHelper::btn(Url::to(['trips/new']), 'Add a trip', 'btn btn-secondary btn-block btn-breadcrumb', true) ?>
	</div>
	<div class="col-md-auto d-lg-block d-print-none">
		<button type="button" class="btn btn-secondary btn-block btn-breadcrumb" onclick="window.history.back()">Back</button>
	</div>
</div>

<div class="row">
	<div class="col-md-auto">
		<h1 class="text-center"><?= $this->title ?></h1>
	</div>
	<div class="w-100"></div>
	<div class="col">
		<hr class="m-0">
	</div>
	<div class="w-100"></div>
	<div class="col">
	<?= GridView::widget([
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'tableOptions' => [
			'class' => 'table-sm',
		],
		'rowOptions' => function($model, $key, $index, $column){
		    if($index == 0){
		        return ['class' => 'table-primary'];
		    }
		},
		'columns' => [
		   [
				'vAlign' => 'middle',
				'hAlign' => 'middle',
				'mergeHeader' => true,
				'label' => 'Trip Details',
				'format' => 'html',
				'class' => '\kartik\grid\DataColumn',
				'value' => function ($model) {
					return '<div class="text-center"><a href="'.Url::to(['trips/view', 'id'=>$model->id]).'" class="btn btn-sm btn-light"><i class="fas fa-plus"></i></a></div>';
				},
				'width' => '100px',
			],
			[
				'attribute' => 'id',
				'vAlign' => 'middle',
				'width' => '80px',
				'format' => 'raw',
			],
			[
				'attribute' => 'trips_types_id', 
				'vAlign' => 'middle',
				'value' => function ($model, $key, $index, $widget) { 
					return $model->tripsType->name_en;
				},
				'filterType' => GridView::FILTER_SELECT2,
				'filter' => $trips_types, 
				'filterWidgetOptions' => [
					'pluginOptions' => ['allowClear' => true],
				],
				'filterInputOptions' => ['placeholder' => ''],
				'format' => 'raw'
			],
			[
				'attribute' => 'countries_id', 
				'vAlign' => 'middle',
				'value' => function ($model, $key, $index, $widget) { 
					return $model->country->country_en;
				},
				'filterType' => GridView::FILTER_SELECT2,
				'filter' => $countries, 
				'filterWidgetOptions' => [
					'pluginOptions' => ['allowClear' => true],
				],
				'filterInputOptions' => ['placeholder' => ''],
				'format' => 'raw',
			],
			[
				//'attribute' => 'searchDateRange',
				'attribute' => 'filter_date_range',
				'vAlign' => 'middle',
				'width' => '280px',

				'value' => function ($model, $key, $index, $widget) {

					return Yii::$app->formatter->format($model->departure_date, 'date') . 
						' - ' . 
						Yii::$app->formatter->format($model->arrival_date, 'date');
				},

				'filterType' => GridView::FILTER_DATE_RANGE,
				'filterWidgetOptions' => [
					'startAttribute' => 'departure_date',
					'endAttribute' => 'arrival_date',
					'convertFormat'=>true,
					'hideInput' => true,
					//'name'=>'date_range_1',
					//'value'=>'01-Jan-14 to 20-Feb-14',
					'pluginOptions' => [
						'autoApply' => true,
						'locale'=>[
							'format'=>'d/m/Y',
							'separator'=>' - ',
						],
						'opens'=>'center',
					],
				],
				'filterInputOptions' => ['class'=>'form-control'],
				'format' => 'raw',
			],
			[
				'label' => 'Days', 
				'vAlign' => 'middle',
				'width' => '50px',
				'value' => function ($model, $key, $index, $widget) { 
					return $model->days();
				},
				'format' => 'raw',
			],
			[
				'attribute' => 'status', 
				'vAlign' => 'middle',
				'width' => '200px',
				'value' => function ($model, $key, $index, $widget) { 
					return $model->getStatusShort();
				},
				'filterType' => GridView::FILTER_SELECT2,
				'filter' => (new Trips)->names_filter, 
				'filterWidgetOptions' => [
					'pluginOptions' => ['allowClear' => true],
				],
				'filterInputOptions' => ['placeholder' => ''],
				'format' => 'raw',
			],
			[
				'class' => '\kartik\grid\ActionColumn',
				'template' => '{update} {delete}',
				'visibleButtons' => [
					'update' => function ($model, $key, $index) {
						return $model->canUpdate();
					},
					
					'delete' => function ($model, $key, $index) {
						return $model->canDelete();
					},
					
				],
			],
		],
	]); ?>
	</div>
</div>