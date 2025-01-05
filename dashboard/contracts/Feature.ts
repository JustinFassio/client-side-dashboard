import { createElement } from '@wordpress/element';

export interface FeatureMetadata {
  name: string;
  description: string;
  order?: number;
}

export interface FeatureContext {
  dispatch: (scope: string) => (action: any) => void;
  apiUrl: string;
  nonce: string;
  debug?: boolean;
}

export interface FeatureRenderProps {
  userId: number;
}

export interface Feature {
  readonly identifier: string;
  readonly metadata: FeatureMetadata;
  register(context: FeatureContext): Promise<void>;
  init(): Promise<void>;
  isEnabled(): boolean;
  render(props: FeatureRenderProps): JSX.Element | null;
  cleanup(): Promise<void>;
  onNavigate?(): void;
  onUserChange?(userId: number): void;
}

export interface FeatureEvents {
  'feature.registered': { identifier: string; metadata: FeatureMetadata };
  'feature.initialized': { identifier: string };
  'feature.error': { identifier: string; error: Error };
  'feature.navigate': { identifier: string };
  'feature.userChange': { identifier: string; userId: number };
  [key: string]: any;
} 