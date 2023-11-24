<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCalendarRequest;
use App\Http\Requests\UpdateCalendarRequest;
use App\Http\Requests\UpdateSectionsRequest;
use App\Http\Resources\CalendarResource;
use App\Http\Resources\Collections\CalendarCollection;
use App\Models\Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class CalendarController extends Controller
{
    public function index(Request $request): CalendarCollection
    {
        $user = auth()->user();

        return new CalendarCollection($user->calendars->load('academicCharge'));
    }

    public function show(Calendar $calendar): CalendarResource
    {
        $this->authorize('view', $calendar);

        $calendar->load('academicCharge', 'sections', 'calendarable');
        return new CalendarResource($calendar);
    }

    public function store(StoreCalendarRequest $request): CalendarResource 
    {
        // Authorization handled inside form request
        $validated = $request->validated();
        
        // For some reason, the passedValidated does not overwrite the calendarable_type
        $validated['calendarable']['type'] = 'App\Models\\' . ucfirst($validated['calendarable']['type']);

        $calendar = Calendar::create($validated + [
            'uuid' => Str::uuid(), // Always generate a new uuid
            'user_id' => auth()->user()->id,
            'calendarable_type' => $validated['calendarable']['type'],
            'calendarable_id' => $validated['calendarable']['id'],
            'academic_charge_id' => $validated['academic_charge']['id'],
        ]);

        return new CalendarResource($calendar);
    }

    public function update(UpdateCalendarRequest $request, Calendar $calendar): CalendarResource
    {
        // Authorization handled inside form request
        $validated = $request->validated();

        $calendar->update($validated);
        
        return new CalendarResource($calendar);
    }

    public function destroy(Calendar $calendar)
    {
        $this->authorize('destroy', $calendar);

        $calendar->delete();

        return response()->json(['message' => 'Calendar deleted'], 204);
    }

    // Section related functions
    public function sections(Calendar $calendar)
    {
        $this->authorize('view', $calendar);

        return $calendar->sections;
    }

    /**
     * This function handles the update, add and delete calendar sections.
     * As the request should have an array or null corresponding to the
     * sections attached to the calendar. Null removes all sections.
     * 
     * TODO: Normalize the return of the function
     */
    public function updateSections(UpdateSectionsRequest $request, Calendar $calendar)
    {
        info('[Calendar Controller] Pre request validations');
        $validated = $request->validated();
        info('[Calendar Controller] Post request validations');

        // TODO: Check if all sections are from the same academic charge
        // TODO: Check if calendarable type id is the same as section school or career

        // sync sections
        $calendar->sections()->sync($validated['sections']);
        
        return new CalendarResource($calendar);
    }
}
