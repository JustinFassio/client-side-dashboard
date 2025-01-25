import React from 'react';
import './buttons.css';

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary';
  feature?: 'physical' | 'profile';
  children: React.ReactNode;
  isLoading?: boolean;
}

export const Button: React.FC<ButtonProps> = ({
  children,
  variant,
  feature,
  className = '',
  isLoading = false,
  disabled,
  ...props
}) => {
  const baseClass = 'btn';
  const classes = [
    baseClass,
    variant && `btn--${variant}`,
    feature && `btn--feature-${feature}`,
    isLoading && 'btn--loading',
    disabled && 'btn--disabled',
    className
  ].filter(Boolean).join(' ');

  return (
    <button 
      className={classes}
      disabled={isLoading || disabled}
      aria-busy={isLoading}
      {...props}
    >
      <span className="btn__content">
        {isLoading ? 'Loading...' : children}
      </span>
    </button>
  );
}; 