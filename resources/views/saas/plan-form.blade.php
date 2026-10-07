@csrf @if($plan?->exists) @method('PUT') @endif
@php($restorePlanInput = old('_plan_editor') === (string) ($plan?->id ?? 'new'))
<input type="hidden" name="_plan_editor" value="{{ $plan?->id ?? 'new' }}">
<div class="fields">
@foreach(['name'=>['Name','text',$plan?->name],'price'=>['Monthly reference price','number',$plan?->price ?? 0],'currency'=>['Currency','text',$plan?->currency ?? 'INR'],'max_users'=>['Team members','number',$plan?->max_users],'max_jobs'=>['Stored jobs','number',$plan?->max_jobs],'max_candidates'=>['Candidates','number',$plan?->max_candidates],'storage_mb'=>['Local storage (MB)','number',$plan?->storage_mb]] as $field=>[$label,$type,$value])
<div><label class="bs-set-lbl" for="{{ $field }}-{{ $plan?->id ?? 'new' }}">{{ $label }}</label><input class="bs-f-input" id="{{ $field }}-{{ $plan?->id ?? 'new' }}" name="{{ $field }}" type="{{ $type }}" value="{{ $restorePlanInput ? old($field,$value) : $value }}" @if(in_array($field,['name','slug','price','currency'])) required @endif @if($type==='number') min="{{ $field==='price' ? 0 : 1 }}" step="{{ $field==='price' ? '0.01' : '1' }}" @endif @if($field==='name') maxlength="100" @elseif($field==='slug') pattern="[a-z0-9-]+" maxlength="50" @elseif($field==='currency') pattern="[A-Z]{3}" maxlength="3" @elseif(str_starts_with($field,'max_') || $field==='storage_mb') placeholder="Blank = unlimited" @endif></div>
@endforeach
@foreach(['enabled'=>'Available for assignment','public'=>'Show publicly'] as $field=>$label)
@php($selected = $restorePlanInput ? old($field) : ($plan ? (int) $plan->$field : 1))
<div><label class="bs-set-lbl" for="{{ $field }}-{{ $plan?->id ?? 'new' }}">{{ $label }}</label><select class="bs-f-sel" id="{{ $field }}-{{ $plan?->id ?? 'new' }}" name="{{ $field }}"><option value="1" @selected((string)$selected==='1')>{{ $field==='enabled' ? 'Enabled' : 'Yes' }}</option><option value="0" @selected((string)$selected==='0')>{{ $field==='enabled' ? 'Disabled' : 'No' }}</option></select></div>
@endforeach
</div><button type="submit">{{ $plan ? 'Save changes' : 'Create plan' }}</button>
