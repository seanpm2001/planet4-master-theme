const {__} = wp.i18n;

export const GridView = ({onClick}) => (
  <svg title={__('Grid layout', 'planet4-master-theme')} onClick={onClick} xmlns="http://www.w3.org/2000/svg" width={40} height={40} viewBox="0 0 40 40" fill="none">
    <mask id="mask0_3203_16595" style={{maskType: 'alpha'}} maskUnits="userSpaceOnUse" x={8} y={8} width={24} height={24}>
      <rect x={8} y={8} width={24} height={24} fill="#D9D9D9" />
    </mask>
    <g mask="url(#mask0_3203_16595)">
      <path d="M11 19V11H19V19H11ZM11 29V21H19V29H11ZM21 19V11H29V19H21ZM21 29V21H29V29H21Z" fill="#1C1C1C" />
    </g>
  </svg>
);
