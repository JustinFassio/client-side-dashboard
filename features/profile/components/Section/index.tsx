import React from 'react';

interface SectionProps {
    title: string;
    children: React.ReactNode;
}

export const Section: React.FC<SectionProps> = ({ title, children }) => {
    return (
        <section className="form-section">
            <h2 className="form-section__title">{title}</h2>
            <div className="form-section__content">
                {children}
            </div>
        </section>
    );
}; 