import { HelpCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useAdminTourContext } from '@/components/admin/AdminTourProvider';

export function AdminTourReplayButton() {
    const { currentTourKey, replayTour } = useAdminTourContext();

    if (!currentTourKey) {
        return null;
    }

    return (
        <Button
            type="button"
            variant="ghost"
            size="sm"
            className="hidden sm:inline-flex gap-1.5 text-muted-foreground"
            onClick={replayTour}
        >
            <HelpCircle className="size-4" />
            Panduan
        </Button>
    );
}
