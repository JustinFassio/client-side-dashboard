import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Button } from '../index';

describe('Button', () => {
  it('renders with default props', () => {
    render(<Button>Click me</Button>);
    
    const button = screen.getByRole('button', { name: /click me/i });
    expect(button).toBeInTheDocument();
    expect(button).toHaveClass('btn');
    expect(button).not.toHaveClass('btn--primary');
    expect(button).not.toBeDisabled();
  });

  it('renders with primary variant', () => {
    render(<Button variant="primary">Primary Button</Button>);
    
    const button = screen.getByRole('button', { name: /primary button/i });
    expect(button).toHaveClass('btn--primary');
  });

  it('renders with secondary variant', () => {
    render(<Button variant="secondary">Secondary Button</Button>);
    
    const button = screen.getByRole('button', { name: /secondary button/i });
    expect(button).toHaveClass('btn--secondary');
  });

  it('handles click events', async () => {
    const handleClick = jest.fn();
    render(<Button onClick={handleClick}>Click me</Button>);
    
    const button = screen.getByRole('button', { name: /click me/i });
    await userEvent.click(button);
    
    expect(handleClick).toHaveBeenCalledTimes(1);
  });

  it('can be disabled', () => {
    render(<Button disabled>Disabled Button</Button>);
    
    const button = screen.getByRole('button', { name: /disabled button/i });
    expect(button).toBeDisabled();
    expect(button).toHaveClass('btn--disabled');
  });

  it('does not trigger click when disabled', async () => {
    const handleClick = jest.fn();
    render(<Button disabled onClick={handleClick}>Disabled Button</Button>);
    
    const button = screen.getByRole('button', { name: /disabled button/i });
    await userEvent.click(button);
    
    expect(handleClick).not.toHaveBeenCalled();
  });

  it('shows loading state', () => {
    render(<Button isLoading>Click me</Button>);
    
    const button = screen.getByRole('button', { name: 'Loading...' });
    expect(button).toHaveAttribute('aria-busy', 'true');
    expect(button).toHaveClass('btn--loading');
    expect(button).toBeDisabled();
  });

  it('renders with custom className', () => {
    render(<Button className="custom-class">Custom Button</Button>);
    
    const button = screen.getByRole('button', { name: /custom button/i });
    expect(button).toHaveClass('btn', 'custom-class');
  });

  it('forwards additional props to button element', () => {
    render(
      <Button data-testid="custom-button" aria-label="Custom button">
        Button Text
      </Button>
    );
    
    const button = screen.getByTestId('custom-button');
    expect(button).toHaveAttribute('aria-label', 'Custom button');
  });
}); 