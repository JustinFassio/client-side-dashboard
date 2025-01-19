import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Button } from '../index';

describe('Button Component', () => {
  it('renders with default props', () => {
    render(<Button>Click me</Button>);
    const button = screen.getByRole('button', { name: /click me/i });
    
    expect(button).toBeInTheDocument();
    expect(button).toHaveClass('btn', 'btn--primary');
  });

  it('renders with different variants', () => {
    const { rerender } = render(
      <Button variant="secondary">Secondary Button</Button>
    );
    
    let button = screen.getByRole('button');
    expect(button).toHaveClass('btn--secondary');
    
    rerender(<Button variant="primary">Primary Button</Button>);
    button = screen.getByRole('button');
    expect(button).toHaveClass('btn--primary');
  });

  it('applies feature-specific styles', () => {
    render(<Button feature="physical">Physical Button</Button>);
    const button = screen.getByRole('button');
    
    expect(button).toHaveClass('btn--feature-physical');
  });

  it('combines custom className with default classes', () => {
    render(<Button className="custom-class">Custom Button</Button>);
    const button = screen.getByRole('button');
    
    expect(button).toHaveClass('btn', 'btn--primary', 'custom-class');
  });

  it('handles click events', async () => {
    const handleClick = jest.fn();
    render(<Button onClick={handleClick}>Click me</Button>);
    
    const button = screen.getByRole('button');
    await userEvent.click(button);
    
    expect(handleClick).toHaveBeenCalledTimes(1);
  });

  it('handles disabled state', () => {
    render(<Button disabled>Disabled Button</Button>);
    const button = screen.getByRole('button');
    
    expect(button).toBeDisabled();
    expect(button).toHaveClass('btn');
  });

  it('passes through additional HTML button attributes', () => {
    render(
      <Button 
        type="submit"
        aria-label="Submit form"
        data-testid="submit-btn"
      >
        Submit
      </Button>
    );
    
    const button = screen.getByRole('button');
    expect(button).toHaveAttribute('type', 'submit');
    expect(button).toHaveAttribute('aria-label', 'Submit form');
    expect(button).toHaveAttribute('data-testid', 'submit-btn');
  });
}); 