const {__} = wp.i18n;

export const ListView = ({onClick}) => (
  <svg title={__('List layout', 'planet4-master-theme')} onClick={onClick} xmlns="http://www.w3.org/2000/svg" width={40} height={40} viewBox="0 0 40 40" fill="none">
    <mask id="mask0_3203_16598" style={{maskType: 'alpha'}} maskUnits="userSpaceOnUse" x={8} y={8} width={24} height={24}>
      <rect x={8} y={8} width={24} height={24} fill="#D9D9D9" />
    </mask>
    <g mask="url(#mask0_3203_16598)">
      <path d="M17 28H28C28.55 28 29.0208 27.8042 29.4125 27.4125C29.8042 27.0208 30 26.55 30 26V24H17V28ZM10 16H15V12H12C11.45 12 10.9792 12.1958 10.5875 12.5875C10.1958 12.9792 10 13.45 10 14V16ZM10 22H15V18H10V22ZM12 28H15V24H10V26C10 26.55 10.1958 27.0208 10.5875 27.4125C10.9792 27.8042 11.45 28 12 28ZM17 22H30V18H17V22ZM17 16H30V14C30 13.45 29.8042 12.9792 29.4125 12.5875C29.0208 12.1958 28.55 12 28 12H17V16Z" fill="#1C1C1C" />
    </g>
  </svg>
);
