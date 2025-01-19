import React from 'react';
import './buttons.css';

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary';
  feature?: 'physical' | 'profile';
  children: React.ReactNode;
}

export const Button: React.FC<ButtonProps> = ({
  children,
  variant = 'primary',
  feature,
  className = '',
  ...props
}) => {
  const baseClass = 'btn';
  const classes = [
    baseClass,
    `btn--${variant}`,
    feature && `btn--feature-${feature}`,
    className
  ].filter(Boolean).join(' ');

  return (
    <button 
      className={classes}
      {...props}
    >
      {children}
    </button>
  );
}; 