import { createElement } from '@wordpress/element';

export interface FeatureMetadata {
  name: string;
  description: string;
  icon?: string | JSX.Element;
  order?: number;
}

export interface FeatureContext {
  dispatch: (storeName: string) => any;
  userId: number;
  nonce: string;
  apiUrl: string;
}

export interface Feature {
  // Required properties
  readonly identifier: string;
  readonly metadata: FeatureMetadata;
  
  // Lifecycle methods
  register(context: FeatureContext): Promise<void> | void;
  init(): Promise<void> | void;
  cleanup(): Promise<void> | void;
  
  // State methods
  isEnabled(): boolean;
  
  // Rendering
  render(): JSX.Element | null;
  
  // Optional methods
  onNavigate?(): void;
  onUserChange?(userId: number): void;
} 