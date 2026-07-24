<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AcademicResourceController extends Controller
{
    public function index(Request $request, string $resource): Response
    {
        $this->ensureResource($resource);
        $search = $request->string('search')->toString();
        $configuration = $this->configuration($resource);

        if ($resource === 'programs') {
            return $this->programIndex($search);
        }

        $records = match ($resource) {
            'faculties' => Faculty::query()->withCount('programs')
                ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"))),
            'programs' => Program::query()->with('faculty')->withCount(['courses', 'classGroups'])
                ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"))),
            'academic-years' => AcademicYear::query()->withCount('semesters')
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%")),
            'semesters' => Semester::query()->with('academicYear')->withCount('classGroups')
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%")),
            'courses' => Course::query()->with('program')->withCount('offerings')
                ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"))),
            default => abort(404),
        };

        return Inertia::render('academic/index', [
            'resource' => $resource,
            'configuration' => $configuration,
            'records' => $records->latest()->paginate(10)->withQueryString()
                ->through(fn (Model $record): array => $this->row($resource, $record)),
            'fields' => $this->fields($resource),
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request, string $resource): RedirectResponse
    {
        $this->ensureResource($resource);
        $validated = $request->validate($this->rules($resource));

        $this->modelClass($resource)::query()->create($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->configuration($resource)['singular'].' created.'),
        ]);

        return $resource === 'programs'
            ? back()
            : to_route('academic.index', ['resource' => $resource]);
    }

    public function update(Request $request, string $resource, int $record): RedirectResponse
    {
        $this->ensureResource($resource);
        $model = $this->findRecord($resource, $record);
        $model->update($request->validate($this->rules($resource, $model)));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->configuration($resource)['singular'].' updated.'),
        ]);

        return $resource === 'programs'
            ? back()
            : to_route('academic.index', ['resource' => $resource]);
    }

    public function destroy(string $resource, int $record): RedirectResponse
    {
        $this->ensureResource($resource);
        $model = $this->findRecord($resource, $record);

        if ($this->isInUse($resource, $model)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Remove its related academic records before deleting it.'),
            ]);

            return back();
        }

        $model->delete();
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->configuration($resource)['singular'].' deleted.'),
        ]);

        return $resource === 'programs'
            ? back()
            : to_route('academic.index', ['resource' => $resource]);
    }

    public function showFaculty(Faculty $faculty): Response
    {
        $faculty->load([
            'programs' => fn ($query) => $query
                ->with(['courses' => fn ($query) => $query
                    ->withCount('offerings')
                    ->orderBy('year_level')
                    ->orderBy('semester_number')
                    ->orderBy('name')])
                ->withCount(['courses', 'classGroups'])
                ->orderBy('name'),
        ]);

        return Inertia::render('academic/programs/show', [
            'facultyOptions' => $this->facultyOptions(),
            'faculty' => [
                'id' => $faculty->id,
                'code' => $faculty->code,
                'name' => $faculty->name,
                'programs_count' => $faculty->programs->count(),
                'courses_count' => $faculty->programs->sum('courses_count'),
                'programs' => $faculty->programs->map(fn (Program $program): array => [
                    'id' => $program->id,
                    'code' => $program->code,
                    'name' => $program->name,
                    'faculty_id' => (string) $program->faculty_id,
                    'courses_count' => (int) $program->getAttribute('courses_count'),
                    'classes_count' => (int) $program->getAttribute('class_groups_count'),
                    'courses' => $program->courses->map(fn (Course $course): array => [
                        'id' => $course->id,
                        'code' => $course->code,
                        'name' => $course->name,
                        'credits' => $course->credits,
                        'description' => $course->description,
                        'year_level' => $course->year_level,
                        'semester_number' => $course->semester_number,
                        'classes_count' => (int) $course->getAttribute('offerings_count'),
                    ])->values()->all(),
                ])->values()->all(),
            ],
        ]);
    }

    public function storeProgramCourse(Request $request, Program $program): RedirectResponse
    {
        $program->courses()->create($request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('courses')
                    ->where('program_id', $program->id)
                    ->where('year_level', $request->input('year_level'))
                    ->where('semester_number', $request->input('semester_number')),
            ],
            'credits' => ['required', 'integer', 'between:1,30'],
            'year_level' => ['required', 'integer', 'between:1,5'],
            'semester_number' => ['required', 'integer', 'between:1,2'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Course created.'),
        ]);

        return back();
    }

    public function updateProgramCourse(Request $request, Program $program, Course $course): RedirectResponse
    {
        abort_unless($course->program_id === $program->id, 404);

        $course->update($request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('courses')
                    ->where('program_id', $program->id)
                    ->where('year_level', $request->input('year_level'))
                    ->where('semester_number', $request->input('semester_number'))
                    ->ignore($course->id),
            ],
            'credits' => ['required', 'integer', 'between:1,30'],
            'year_level' => ['required', 'integer', 'between:1,5'],
            'semester_number' => ['required', 'integer', 'between:1,2'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Course updated.'),
        ]);

        return back();
    }

    public function destroyProgramCourse(Program $program, Course $course): RedirectResponse
    {
        abort_unless($course->program_id === $program->id, 404);

        if ($course->offerings()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Remove the course from its classes before deleting it.'),
            ]);

            return back();
        }

        $course->delete();
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Course deleted.'),
        ]);

        return back();
    }

    /** @return array{title: string, singular: string, description: string, columns: list<array{key: string, label: string}>} */
    private function configuration(string $resource): array
    {
        return match ($resource) {
            'faculties' => [
                'title' => 'Faculties', 'singular' => 'Faculty',
                'description' => 'Manage the university faculties.',
                'columns' => [['key' => 'code', 'label' => 'Code'], ['key' => 'name', 'label' => 'Faculty'], ['key' => 'related', 'label' => 'Programs']],
            ],
            'programs' => [
                'title' => 'Programs', 'singular' => 'Program',
                'description' => 'Manage degree programs and their faculties.',
                'columns' => [['key' => 'code', 'label' => 'Code'], ['key' => 'name', 'label' => 'Program'], ['key' => 'parent', 'label' => 'Faculty'], ['key' => 'related', 'label' => 'Courses / Classes']],
            ],
            'academic-years' => [
                'title' => 'Academic Years', 'singular' => 'Academic year',
                'description' => 'Configure university academic periods.',
                'columns' => [['key' => 'name', 'label' => 'Academic year'], ['key' => 'period', 'label' => 'Period'], ['key' => 'status', 'label' => 'Status'], ['key' => 'related', 'label' => 'Semesters']],
            ],
            'semesters' => [
                'title' => 'Semesters', 'singular' => 'Semester',
                'description' => 'Manage semesters inside each academic year.',
                'columns' => [['key' => 'name', 'label' => 'Semester'], ['key' => 'parent', 'label' => 'Academic year'], ['key' => 'period', 'label' => 'Period'], ['key' => 'related', 'label' => 'Classes']],
            ],
            'courses' => [
                'title' => 'Courses', 'singular' => 'Course',
                'description' => 'Manage reusable subjects in each program.',
                'columns' => [['key' => 'code', 'label' => 'Code'], ['key' => 'name', 'label' => 'Course'], ['key' => 'parent', 'label' => 'Program'], ['key' => 'placement', 'label' => 'Curriculum'], ['key' => 'related', 'label' => 'Credits / Classes']],
            ],
            default => abort(404),
        };
    }

    /** @return list<array<string, mixed>> */
    private function fields(string $resource): array
    {
        return match ($resource) {
            'faculties' => [
                ['name' => 'name', 'label' => 'Faculty name', 'type' => 'text', 'placeholder' => 'Faculty of Science and Technology'],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'placeholder' => 'FST'],
            ],
            'programs' => [
                ['name' => 'name', 'label' => 'Program name', 'type' => 'text', 'placeholder' => 'Bachelor of Information Technology'],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'placeholder' => 'BIT'],
                ['name' => 'faculty_id', 'label' => 'Faculty', 'type' => 'select', 'options' => $this->facultyOptions()],
            ],
            'academic-years' => [
                ['name' => 'name', 'label' => 'Academic year', 'type' => 'text', 'placeholder' => '2026-2027'],
                ['name' => 'starts_at', 'label' => 'Start date', 'type' => 'date'],
                ['name' => 'ends_at', 'label' => 'End date', 'type' => 'date'],
                ['name' => 'is_active', 'label' => 'Status', 'type' => 'select', 'defaultValue' => '1', 'options' => [['value' => '1', 'label' => 'Active'], ['value' => '0', 'label' => 'Inactive']]],
            ],
            'semesters' => [
                ['name' => 'name', 'label' => 'Semester name', 'type' => 'text', 'placeholder' => 'Semester 1'],
                ['name' => 'academic_year_id', 'label' => 'Academic year', 'type' => 'select', 'options' => $this->academicYearOptions()],
                ['name' => 'starts_at', 'label' => 'Start date', 'type' => 'date'],
                ['name' => 'ends_at', 'label' => 'End date', 'type' => 'date'],
            ],
            'courses' => [
                ['name' => 'name', 'label' => 'Course name', 'type' => 'text', 'placeholder' => 'Advanced PHP and MySQL'],
                ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'placeholder' => 'IT304'],
                ['name' => 'program_id', 'label' => 'Program', 'type' => 'select', 'options' => $this->programOptions()],
                ['name' => 'year_level', 'label' => 'Study year', 'type' => 'select', 'options' => $this->studyYearOptions()],
                ['name' => 'semester_number', 'label' => 'Curriculum semester', 'type' => 'select', 'options' => $this->curriculumSemesterOptions()],
                ['name' => 'credits', 'label' => 'Credits', 'type' => 'number', 'placeholder' => '3'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'placeholder' => 'Course summary and learning outcomes'],
            ],
            default => abort(404),
        };
    }

    /** @return array<string, mixed> */
    private function rules(string $resource, ?Model $record = null): array
    {
        return match ($resource) {
            'faculties' => [
                'name' => ['required', 'string', 'max:150'],
                'code' => ['required', 'string', 'max:30', Rule::unique('faculties')->ignore($record?->getKey())],
            ],
            'programs' => [
                'name' => ['required', 'string', 'max:150'],
                'code' => ['required', 'string', 'max:30', Rule::unique('programs')->ignore($record?->getKey())],
                'faculty_id' => ['required', 'integer', 'exists:faculties,id'],
            ],
            'academic-years' => [
                'name' => ['required', 'string', 'max:30', Rule::unique('academic_years')->ignore($record?->getKey())],
                'starts_at' => ['required', 'date'],
                'ends_at' => ['required', 'date', 'after:starts_at'],
                'is_active' => ['required', 'boolean'],
            ],
            'semesters' => [
                'name' => ['required', 'string', 'max:60', Rule::unique('semesters')->where('academic_year_id', request()->input('academic_year_id'))->ignore($record?->getKey())],
                'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
                'starts_at' => ['required', 'date'],
                'ends_at' => ['required', 'date', 'after:starts_at'],
            ],
            'courses' => [
                'name' => ['required', 'string', 'max:150'],
                'code' => [
                    'required',
                    'string',
                    'max:30',
                    Rule::unique('courses')
                        ->where('program_id', request()->input('program_id'))
                        ->where('year_level', request()->input('year_level'))
                        ->where('semester_number', request()->input('semester_number'))
                        ->ignore($record?->getKey()),
                ],
                'program_id' => ['required', 'integer', 'exists:programs,id'],
                'year_level' => ['required', 'integer', 'between:1,5'],
                'semester_number' => ['required', 'integer', 'between:1,2'],
                'credits' => ['required', 'integer', 'between:1,30'],
                'description' => ['nullable', 'string', 'max:2000'],
            ],
            default => abort(404),
        };
    }

    /** @return class-string<Model> */
    private function modelClass(string $resource): string
    {
        return match ($resource) {
            'faculties' => Faculty::class,
            'programs' => Program::class,
            'academic-years' => AcademicYear::class,
            'semesters' => Semester::class,
            'courses' => Course::class,
            default => abort(404),
        };
    }

    private function findRecord(string $resource, int $record): Model
    {
        return $this->modelClass($resource)::query()->findOrFail($record);
    }

    /** @return array<string, mixed> */
    private function formValues(string $resource, Model $record): array
    {
        $values = $record->only(collect($this->fields($resource))->pluck('name')->all());

        if ($record instanceof AcademicYear || $record instanceof Semester) {
            $values['starts_at'] = $record->starts_at->format('Y-m-d');
            $values['ends_at'] = $record->ends_at->format('Y-m-d');
        }

        if ($record instanceof AcademicYear) {
            $values['is_active'] = $record->is_active ? '1' : '0';
        }

        return ['id' => $record->getKey(), ...$values];
    }

    /** @return array<string, mixed> */
    private function row(string $resource, Model $record): array
    {
        return [
            ...match (true) {
                $record instanceof Faculty => ['id' => $record->id, 'code' => $record->code, 'name' => $record->name, 'related' => $record->programs_count.' programs'],
                $record instanceof Program => ['id' => $record->id, 'code' => $record->code, 'name' => $record->name, 'parent' => $record->faculty->name, 'related' => $record->courses_count.' courses · '.$record->class_groups_count.' classes'],
                $record instanceof AcademicYear => ['id' => $record->id, 'name' => $record->name, 'period' => $record->starts_at->format('M j, Y').' – '.$record->ends_at->format('M j, Y'), 'status' => $record->is_active ? 'Active' : 'Inactive', 'related' => $record->semesters_count.' semesters'],
                $record instanceof Semester => ['id' => $record->id, 'name' => $record->name, 'parent' => $record->academicYear->name, 'period' => $record->starts_at->format('M j, Y').' – '.$record->ends_at->format('M j, Y'), 'related' => $record->class_groups_count.' classes'],
                $record instanceof Course => ['id' => $record->id, 'code' => $record->code, 'name' => $record->name, 'parent' => $record->program->name, 'placement' => "Year {$record->year_level} · Semester {$record->semester_number}", 'related' => $record->credits.' credits · '.$record->offerings_count.' classes'],
                default => throw new \LogicException('Unsupported academic model.'),
            },
            'form' => $this->formValues($resource, $record),
        ];
    }

    private function isInUse(string $resource, Model $record): bool
    {
        return match (true) {
            $record instanceof Faculty => $record->programs()->exists(),
            $record instanceof Program => $record->courses()->exists() || $record->classGroups()->exists(),
            $record instanceof AcademicYear => $record->semesters()->exists(),
            $record instanceof Semester => $record->classGroups()->exists(),
            $record instanceof Course => $record->offerings()->exists(),
            default => throw new \LogicException('Unsupported academic model.'),
        };
    }

    /** @return array<int, array{value: string, label: string}> */
    private function facultyOptions(): array
    {
        return Faculty::query()->orderBy('name')->get()->map(fn (Faculty $item) => ['value' => (string) $item->id, 'label' => "{$item->code} — {$item->name}"])->values()->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    private function programOptions(): array
    {
        return Program::query()->orderBy('name')->get()->map(fn (Program $item) => ['value' => (string) $item->id, 'label' => "{$item->code} — {$item->name}"])->values()->all();
    }

    /** @return list<array{value: string, label: string}> */
    private function studyYearOptions(): array
    {
        return [
            ['value' => '1', 'label' => 'Year 1'],
            ['value' => '2', 'label' => 'Year 2'],
            ['value' => '3', 'label' => 'Year 3'],
            ['value' => '4', 'label' => 'Year 4'],
            ['value' => '5', 'label' => 'Year 5'],
        ];
    }

    /** @return list<array{value: string, label: string}> */
    private function curriculumSemesterOptions(): array
    {
        return [
            ['value' => '1', 'label' => 'Semester 1'],
            ['value' => '2', 'label' => 'Semester 2'],
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function academicYearOptions(): array
    {
        return AcademicYear::query()->orderByDesc('starts_at')->get()->map(fn (AcademicYear $item) => ['value' => (string) $item->id, 'label' => $item->name])->values()->all();
    }

    private function ensureResource(string $resource): void
    {
        abort_unless(in_array($resource, ['faculties', 'programs', 'academic-years', 'semesters', 'courses'], true), 404);
    }

    private function programIndex(string $search): Response
    {
        return Inertia::render('academic/programs/index', [
            'faculties' => Faculty::query()
                ->withCount(['programs', 'programs as courses_count' => fn ($query) => $query
                    ->join('courses', 'programs.id', '=', 'courses.program_id')])
                ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('programs', fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"))))
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Faculty $faculty): array => [
                    'id' => $faculty->id,
                    'code' => $faculty->code,
                    'name' => $faculty->name,
                    'programs_count' => (int) $faculty->getAttribute('programs_count'),
                    'courses_count' => (int) $faculty->getAttribute('courses_count'),
                ]),
            'filters' => ['search' => $search],
            'facultyOptions' => $this->facultyOptions(),
        ]);
    }
}
