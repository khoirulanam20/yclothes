import * as React from 'react';
import { cn } from '@/lib/utils';

interface NumberInputProps extends Omit<React.ComponentProps<'input'>, 'onChange'> {
  value: number | string;
  onChange: (value: number) => void;
  min?: number;
  max?: number;
  step?: string;
}

function toNum(v: number | string): number {
  if (v === '' || v === null || v === undefined) return 0;
  const n = Number(v);
  return Number.isNaN(n) ? 0 : n;
}

function NumberInput({ className, value, onChange, min, max, step, ...props }: NumberInputProps) {
  const num = toNum(value);
  const [display, setDisplay] = React.useState(num === 0 ? '' : String(num));

  React.useEffect(() => {
    setDisplay(num === 0 ? '' : String(num));
  }, [num]);

  const handleBlur = (e: React.FocusEvent<HTMLInputElement>) => {
    const n = display === '' ? 0 : Number(display);
    onChange(Number.isNaN(n) ? 0 : n);
    setDisplay(n === 0 ? '' : String(n));
    props.onBlur?.(e);
  };

  return (
    <input
      type="text"
      inputMode="numeric"
      data-slot="input"
      className={cn(
        'h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none selection:bg-primary selection:text-primary-foreground file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm dark:bg-input/30',
        'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
        'aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40',
        className
      )}
      value={display}
      onChange={(e) => setDisplay(e.target.value)}
      onBlur={handleBlur}
      min={min}
      max={max}
      step={step}
      {...props}
    />
  );
}

export { NumberInput, type NumberInputProps };
