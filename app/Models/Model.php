<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;

abstract class Model extends BaseModel {

	static function fillables() {
		return (new static)->fillable;
	}

	static function tablename() {
		return (new static)->getTable();
	}

	static function findBy($attr, $operation, $value = null) {
		return static::where($attr, $operation, $value)->first();
	}

	static function findOrFailBy($attr, $operation, $value = null) {
		return static::where($attr, $operation, $value)->firstOrFail();
	}

	function scopeApproved($query) {
		return $query->where("status", "approved");
	}

	function isApproved() {
		return $this->status == 'approved';
	}

	static function boot() {

		parent::boot();

		static::updating(function($model) {

			if ($model->status == "approved" && empty($model->approved_at)) {
				$model->approved_at = date("Y-m-d H:i:s");
			}

		});

		static::deleting(fn($model) => $model->status = 'deleted');
		static::deleted(fn($model) => $model->deleteChildren());

	}

}