@extends('admin.layouts.app')

@section('content')
<recruitment-application
    :initial-application='@json($application)'
    :stages='@json($stages)'
    :routes='@json([
        "stage" => route("recruitment.applications.stage", $application),
        "assessments" => route("recruitment.applications.assessments", $application),
        "offer" => route("recruitment.applications.offer", $application),
        "sendOffer" => route("recruitment.applications.offer.send", $application),
        "requirements" => url("/admin/recruitment/requirements"),
        "hire" => route("recruitment.applications.hire", $application),
    ])'
    process-url="{{ route('recruitment.process') }}"
    :can-manage='@json(auth()->user()->can("hr.recruitment.manage"))'
></recruitment-application>
@endsection
