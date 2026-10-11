const inputClasses =
  'mt-1 block w-full rounded-lg border px-3 py-2 text-gray-900 shadow-sm focus:outline-none focus:ring-2'

export function Field({ label, name, error, hint, ...props }) {
  return (
    <div>
      <label htmlFor={name} className="block text-sm font-medium text-gray-700">
        {label}
      </label>
      <input
        id={name}
        name={name}
        aria-invalid={Boolean(error)}
        className={`${inputClasses} ${
          error
            ? 'border-red-500 focus:ring-red-200'
            : 'border-gray-300 focus:border-primary focus:ring-primary/30'
        }`}
        {...props}
      />
      {hint && !error && <p className="mt-1 text-xs text-gray-500">{hint}</p>}
      {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
    </div>
  )
}

export function Checkbox({ label, name, error, ...props }) {
  return (
    <div>
      <label htmlFor={name} className="flex items-start gap-2 text-sm text-gray-700">
        <input
          id={name}
          name={name}
          type="checkbox"
          className="mt-0.5 size-4 rounded border-gray-300 text-primary focus:ring-primary"
          {...props}
        />
        <span>{label}</span>
      </label>
      {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
    </div>
  )
}

export function Button({ loading = false, children, className = '', ...props }) {
  return (
    <button
      disabled={loading || props.disabled}
      className={`inline-flex w-full items-center justify-center rounded-lg bg-primary px-4 py-2.5 font-semibold text-white transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:cursor-not-allowed disabled:opacity-60 ${className}`}
      {...props}
    >
      {loading ? 'Veuillez patienter…' : children}
    </button>
  )
}

const alertStyles = {
  error: 'bg-red-50 text-red-800 ring-red-200',
  success: 'bg-green-50 text-green-800 ring-green-200',
  info: 'bg-blue-50 text-blue-800 ring-blue-200',
}

export function Alert({ variant = 'info', children }) {
  return (
    <div role="alert" className={`rounded-lg p-3 text-sm ring-1 ${alertStyles[variant]}`}>
      {children}
    </div>
  )
}