import { format, isValid, parse } from 'date-fns';
import { CalendarIcon, ChevronDownIcon, Clock3 } from 'lucide-react';
import { useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type DatePickerProps = {
    value: string;
    onChange: (value: string) => void;
    id?: string;
    placeholder?: string;
    disabled?: boolean;
    invalid?: boolean;
    required?: boolean;
    includeTime?: boolean;
    className?: string;
};

function parsePickerValue(
    value: string,
    includeTime: boolean,
): Date | undefined {
    if (!value) {
        return undefined;
    }

    const parsed = parse(
        value,
        includeTime ? "yyyy-MM-dd'T'HH:mm" : 'yyyy-MM-dd',
        new Date(),
    );

    return isValid(parsed) ? parsed : undefined;
}

export function DatePicker({
    value,
    onChange,
    id,
    placeholder = 'Pick a date',
    disabled = false,
    invalid = false,
    required = false,
    includeTime = false,
    className,
}: DatePickerProps) {
    const [open, setOpen] = useState(false);
    const selectedDate = useMemo(
        () => parsePickerValue(value, includeTime),
        [includeTime, value],
    );
    const selectedTime =
        includeTime && value.includes('T')
            ? value.split('T')[1]?.slice(0, 5)
            : '';

    const selectDate = (date: Date | undefined) => {
        if (!date) {
            return;
        }

        const dateValue = format(date, 'yyyy-MM-dd');

        if (includeTime) {
            onChange(`${dateValue}T${selectedTime || '23:59'}`);

            return;
        }

        onChange(dateValue);
        setOpen(false);
    };

    const selectTime = (time: string) => {
        const dateValue = selectedDate
            ? format(selectedDate, 'yyyy-MM-dd')
            : format(new Date(), 'yyyy-MM-dd');

        onChange(`${dateValue}T${time || '23:59'}`);
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    aria-invalid={invalid}
                    aria-required={required}
                    data-empty={!selectedDate}
                    className={cn(
                        'w-full justify-between text-left font-normal data-[empty=true]:text-muted-foreground',
                        className,
                    )}
                >
                    <span className="flex min-w-0 items-center gap-2">
                        <CalendarIcon className="size-4 shrink-0" />
                        <span className="truncate">
                            {selectedDate
                                ? format(
                                      selectedDate,
                                      includeTime ? 'PPP p' : 'PPP',
                                  )
                                : placeholder}
                        </span>
                    </span>
                    <ChevronDownIcon className="size-4 shrink-0 opacity-60" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    selected={selectedDate}
                    onSelect={selectDate}
                    defaultMonth={selectedDate}
                    captionLayout="dropdown"
                    startMonth={new Date(2000, 0)}
                    endMonth={new Date(2100, 11)}
                    autoFocus
                />

                {includeTime && (
                    <div className="flex items-end gap-3 border-t p-3">
                        <div className="grid flex-1 gap-1.5">
                            <label
                                htmlFor={`${id ?? 'date-picker'}-time`}
                                className="flex items-center gap-1.5 text-xs font-medium"
                            >
                                <Clock3 className="size-3.5" />
                                Time
                            </label>
                            <Input
                                id={`${id ?? 'date-picker'}-time`}
                                type="time"
                                value={selectedTime || '23:59'}
                                onChange={(event) =>
                                    selectTime(event.target.value)
                                }
                            />
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            onClick={() => setOpen(false)}
                        >
                            Done
                        </Button>
                    </div>
                )}

                {selectedDate && (
                    <div className="flex justify-end border-t p-2">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                onChange('');
                                setOpen(false);
                            }}
                        >
                            Clear date
                        </Button>
                    </div>
                )}
            </PopoverContent>
        </Popover>
    );
}
