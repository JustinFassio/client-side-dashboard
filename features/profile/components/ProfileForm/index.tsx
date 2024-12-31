import { useState } from 'react';
import { LucideIcon } from 'lucide-react';
import './ProfileForm.css';

interface Section {
    id: string;
    title: string;
    icon: LucideIcon;
}

interface ProfileFormProps {
    onSave: (data: any) => void;
    sections: Section[];
}

export const ProfileForm = ({ onSave, sections }: ProfileFormProps) => {
    const [activeSection, setActiveSection] = useState(sections[0].id);
    const [formData, setFormData] = useState({
        age: '',
        gender: '',
        height: '',
        weight: '',
        medicalConditions: '',
        injuries: ''
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onSave(formData);
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    return (
        <div className="profile-form-container">
            <nav className="profile-sections">
                {sections.map(section => (
                    <button
                        key={section.id}
                        className={`section-button ${activeSection === section.id ? 'active' : ''}`}
                        onClick={() => setActiveSection(section.id)}
                    >
                        <section.icon size={20} />
                        <span>{section.title}</span>
                    </button>
                ))}
            </nav>

            <form onSubmit={handleSubmit} className="profile-form">
                {activeSection === 'basic' && (
                    <div className="form-section">
                        <h2>Basic Information</h2>
                        <div className="form-group">
                            <label htmlFor="age">Age</label>
                            <input
                                type="number"
                                id="age"
                                name="age"
                                value={formData.age}
                                onChange={handleInputChange}
                                min="0"
                                max="120"
                            />
                        </div>
                        <div className="form-group">
                            <label htmlFor="gender">Gender</label>
                            <select
                                id="gender"
                                name="gender"
                                value={formData.gender}
                                onChange={handleInputChange}
                            >
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                                <option value="prefer-not-to-say">Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                )}

                {activeSection === 'physical' && (
                    <div className="form-section">
                        <h2>Physical Information</h2>
                        <div className="form-group">
                            <label htmlFor="height">Height (cm)</label>
                            <input
                                type="number"
                                id="height"
                                name="height"
                                value={formData.height}
                                onChange={handleInputChange}
                                min="0"
                                max="300"
                            />
                        </div>
                        <div className="form-group">
                            <label htmlFor="weight">Weight (kg)</label>
                            <input
                                type="number"
                                id="weight"
                                name="weight"
                                value={formData.weight}
                                onChange={handleInputChange}
                                min="0"
                                max="500"
                            />
                        </div>
                    </div>
                )}

                {activeSection === 'medical' && (
                    <div className="form-section">
                        <h2>Medical Information</h2>
                        <div className="form-group">
                            <label htmlFor="medicalConditions">Medical Conditions</label>
                            <textarea
                                id="medicalConditions"
                                name="medicalConditions"
                                value={formData.medicalConditions}
                                onChange={handleInputChange}
                                rows={4}
                                placeholder="List any medical conditions that may affect your training..."
                            />
                        </div>
                    </div>
                )}

                {activeSection === 'injuries' && (
                    <div className="form-section">
                        <h2>Injuries & Limitations</h2>
                        <div className="form-group">
                            <label htmlFor="injuries">Current or Past Injuries</label>
                            <textarea
                                id="injuries"
                                name="injuries"
                                value={formData.injuries}
                                onChange={handleInputChange}
                                rows={4}
                                placeholder="Describe any injuries or physical limitations..."
                            />
                        </div>
                    </div>
                )}

                <div className="form-actions">
                    <button type="submit" className="save-button">
                        Save Profile
                    </button>
                </div>
            </form>
        </div>
    );
}; 